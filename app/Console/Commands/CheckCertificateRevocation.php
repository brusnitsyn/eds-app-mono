<?php

namespace App\Console\Commands;

use App\Facades\Crypto;
use App\Models\Certification;
use App\Services\PythonCertificateParserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckCertificateRevocation extends Command
{
    protected $signature = 'certificates:check-revocation';

    protected $description = 'Проверяет уже загруженные сертификаты по CRL и помечает отозванные (revoked_at)';

    public function handle(PythonCertificateParserService $parser): int
    {
        $certifications = Certification::whereNull('revoked_at')->get();

        $this->info("К проверке: {$certifications->count()} сертификат(ов)");

        $checked = 0;
        $revoked = 0;
        $skipped = 0;

        $this->withProgressBar($certifications, function (Certification $certification) use ($parser, &$checked, &$revoked, &$skipped) {
            match ($this->checkOne($certification, $parser)) {
                'revoked' => $revoked++,
                'checked' => $checked++,
                'skipped' => $skipped++,
            };
        });

        $this->newLine(2);
        $this->info("Проверено: {$checked}, отозвано: {$revoked}, пропущено (нет файла/сети/CRL): {$skipped}");

        return self::SUCCESS;
    }

    /**
     * Сертификаты на диске лежат зашифрованными (см. ProcessCertificateUpload::
     * encryptFilesInDirectory) — расшифровываем не оригинал, а одноразовую копию
     * во временном хранилище, чтобы исходный файл никогда не оказывался на диске
     * в открытом виде.
     *
     * @return 'revoked'|'checked'|'skipped'
     */
    private function checkOne(Certification $certification, PythonCertificateParserService $parser): string
    {
        $relativePath = "{$certification->path_certification}/{$certification->file_certification}";

        if (!Storage::disk('certification')->exists($relativePath)) {
            Log::warning("Проверка отзыва: файл сертификата #{$certification->id} не найден ({$relativePath})");

            return 'skipped';
        }

        $tempRelativePath = 'revocation-check/' . Str::uuid() . '.cer';

        try {
            Storage::disk('temp')->put($tempRelativePath, Storage::disk('certification')->get($relativePath));

            $tempAbsolutePath = Storage::disk('temp')->path($tempRelativePath);
            Crypto::decryptFile($tempAbsolutePath);

            $revocation = $parser->parse($tempAbsolutePath)['revocation'] ?? null;

            if (!($revocation['checked'] ?? false)) {
                return 'skipped';
            }

            if ($revocation['revoked'] ?? false) {
                $certification->update([
                    'revoked_at' => now(),
                    'is_valid' => false,
                ]);

                return 'revoked';
            }

            return 'checked';
        } catch (\Throwable $e) {
            Log::error("Проверка отзыва: ошибка для сертификата #{$certification->id} — " . $e->getMessage());

            return 'skipped';
        } finally {
            Storage::disk('temp')->delete($tempRelativePath);
        }
    }
}

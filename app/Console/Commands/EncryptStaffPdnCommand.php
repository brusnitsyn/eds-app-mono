<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Services\Crypto\PdnCipher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Идемпотентный backfill: шифрует существующие открытые ПДн-поля сотрудника,
 * заполняет hash-колонки точного поиска и контрольную сумму записи
 * (меры ЗНИ, ОЦЛ.2). Безопасно запускать повторно — строки, у которых все
 * поля уже зашифрованы (успешно расшифровываются текущим драйвером),
 * пропускаются.
 */
class EncryptStaffPdnCommand extends Command
{
    protected $signature = 'pdn:encrypt-staff {--dry-run : Не записывать изменения, только показать статистику} {--chunk=200}';

    protected $description = 'Шифрует ПДн-поля сотрудников и заполняет hash-колонки для поиска';

    /**
     * @var array<int, string>
     */
    private array $fields = [
        'snils',
        'inn',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'job_title',
        'mis_login',
    ];

    public function handle(PdnCipher $cipher): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $total = DB::table('staff')->count();
        $this->info("К обработке: {$total} запис(ей)" . ($dryRun ? ' (dry-run, без записи)' : ''));

        $encrypted = 0;
        $alreadyDone = 0;
        $errors = 0;

        $this->withProgressBar(
            DB::table('staff')->orderBy('id')->cursor(),
            function ($row) use ($cipher, $dryRun, &$encrypted, &$alreadyDone, &$errors) {
                try {
                    $plain = [];
                    $allDone = true;

                    foreach ($this->fields as $field) {
                        [$value, $done] = $this->plaintextAndStatus($cipher, $row->$field);
                        $plain[$field] = $value;
                        $allDone = $allDone && $done;
                    }

                    if ($allDone) {
                        $alreadyDone++;

                        return;
                    }

                    $update = [];
                    foreach ($this->fields as $field) {
                        $update[$field] = ($plain[$field] === null || $plain[$field] === '')
                            ? $row->$field
                            : $cipher->encrypt($plain[$field]);
                    }

                    $update['snils_hash'] = $this->hashOrNull($plain['snils'], fn (string $v) => Staff::pdnLookupHash($v));
                    $update['inn_hash'] = $this->hashOrNull($plain['inn'], fn (string $v) => Staff::pdnLookupHash($v));
                    $update['job_title_hash'] = $this->hashOrNull($plain['job_title'], fn (string $v) => Staff::pdnExactHash($v));

                    $update['pdn_checksum'] = hash_hmac(
                        'sha256',
                        implode('|', [$row->id, $plain['snils'], $plain['inn'], $plain['full_name'], $row->dob]),
                        (string) config('security.encryption.pseudonym_key')
                    );

                    if (! $dryRun) {
                        DB::table('staff')->where('id', $row->id)->update($update);
                    }

                    $encrypted++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Staff #{$row->id}: {$e->getMessage()}");
                }
            }
        );

        $this->newLine(2);
        $this->info("Зашифровано: {$encrypted}, уже было зашифровано: {$alreadyDone}, ошибок: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Открытое значение поля и признак того, что оно уже было зашифровано
     * (успешно расшифровывается текущим драйвером). null/'' считаются «готовыми».
     *
     * @return array{0: ?string, 1: bool}
     */
    private function plaintextAndStatus(PdnCipher $cipher, ?string $value): array
    {
        if ($value === null || $value === '') {
            return [$value, true];
        }

        try {
            return [$cipher->decrypt($value), true];
        } catch (Throwable) {
            return [$value, false];
        }
    }

    private function hashOrNull(?string $plain, callable $hasher): ?string
    {
        return ($plain === null || $plain === '') ? null : $hasher($plain);
    }
}

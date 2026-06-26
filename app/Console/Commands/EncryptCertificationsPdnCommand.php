<?php

namespace App\Console\Commands;

use App\Services\Crypto\PdnCipher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Идемпотентный backfill: шифрует существующие открытые serial_number/
 * file_certification/mis_serial_number (мера ЗНИ). Точный поиск/сортировка
 * по этим полям не требуются — hash-колонок нет. Безопасно запускать
 * повторно — строки, у которых все поля уже зашифрованы, пропускаются.
 */
class EncryptCertificationsPdnCommand extends Command
{
    protected $signature = 'pdn:encrypt-certifications {--dry-run : Не записывать изменения, только показать статистику}';

    protected $description = 'Шифрует serial_number/file_certification/mis_serial_number у сертификатов';

    /**
     * @var array<int, string>
     */
    private array $fields = ['serial_number', 'file_certification', 'mis_serial_number'];

    public function handle(PdnCipher $cipher): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $total = DB::table('certifications')->count();
        $this->info("К обработке: {$total} запис(ей)" . ($dryRun ? ' (dry-run, без записи)' : ''));

        $encrypted = 0;
        $alreadyDone = 0;
        $errors = 0;

        $this->withProgressBar(
            DB::table('certifications')->orderBy('id')->cursor(),
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

                    if (! $dryRun) {
                        DB::table('certifications')->where('id', $row->id)->update($update);
                    }

                    $encrypted++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Certification #{$row->id}: {$e->getMessage()}");
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
}

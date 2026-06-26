<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Crypto\PdnCipher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Идемпотентный backfill: шифрует существующие открытые login/email/name
 * и заполняет hash-колонки точного поиска (мера ЗНИ). login/email значимы
 * посимвольно — используется строгий хеш (User::pdnExactHash), а не
 * «нестрогий» pdnLookupHash (см. HasPdnEncryption). Безопасно запускать
 * повторно — строки, у которых все поля уже зашифрованы, пропускаются.
 */
class EncryptUsersPdnCommand extends Command
{
    protected $signature = 'pdn:encrypt-users {--dry-run : Не записывать изменения, только показать статистику}';

    protected $description = 'Шифрует login/email/name у пользователей и заполняет hash-колонки для поиска';

    /**
     * @var array<int, string>
     */
    private array $fields = ['login', 'email', 'name'];

    public function handle(PdnCipher $cipher): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $total = DB::table('users')->count();
        $this->info("К обработке: {$total} запис(ей)" . ($dryRun ? ' (dry-run, без записи)' : ''));

        $encrypted = 0;
        $alreadyDone = 0;
        $errors = 0;

        $this->withProgressBar(
            DB::table('users')->orderBy('id')->cursor(),
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

                    $update['login_hash'] = $this->hashOrNull($plain['login']);
                    $update['email_hash'] = $this->hashOrNull($plain['email']);

                    if (! $dryRun) {
                        DB::table('users')->where('id', $row->id)->update($update);
                    }

                    $encrypted++;
                } catch (Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("User #{$row->id}: {$e->getMessage()}");
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

    private function hashOrNull(?string $plain): ?string
    {
        return ($plain === null || $plain === '') ? null : User::pdnExactHash($plain);
    }
}

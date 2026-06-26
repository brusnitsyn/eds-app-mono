<?php

namespace App\Console\Commands;

use App\Services\Audit\AuditService;
use Illuminate\Console\Command;

/**
 * Проверка целостности журнала аудита (мера РСБ.3, ОЦЛ.1).
 *
 * Запускать по расписанию и при расследовании инцидентов.
 */
class AuditVerifyCommand extends Command
{
    protected $signature = 'audit:verify';

    protected $description = 'Проверить HMAC-подписи и хеш-цепочку журнала аудита';

    public function handle(AuditService $audit): int
    {
        $broken = $audit->verifyIntegrity();

        if ($broken === []) {
            $this->info('Целостность журнала аудита подтверждена.');

            return self::SUCCESS;
        }

        $this->error('Обнаружены нарушения целостности журнала! ID записей: '.implode(', ', $broken));

        return self::FAILURE;
    }
}

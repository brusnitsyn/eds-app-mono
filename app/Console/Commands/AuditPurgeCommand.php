<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * Удаление устаревших записей журнала по сроку хранения (мера РСБ.4, РСБ.8).
 *
 * Срок хранения задаётся AUDIT_RETENTION_DAYS (для УЗ-1 — не менее 3 лет).
 * Перед удалением записи должны быть выгружены в архив/SIEM.
 */
class AuditPurgeCommand extends Command
{
    protected $signature = 'audit:purge {--dry-run : Показать количество без удаления}';

    protected $description = 'Удалить записи журнала аудита старше срока хранения';

    public function handle(): int
    {
        $days = (int) config('audit.retention_days');
        $threshold = now()->subDays($days);

        $query = AuditLog::query()->where('created_at', '<', $threshold);
        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info("К удалению (старше {$days} дн.): {$count} записей.");

            return self::SUCCESS;
        }

        $query->delete();
        $this->info("Удалено записей журнала: {$count}.");

        return self::SUCCESS;
    }
}

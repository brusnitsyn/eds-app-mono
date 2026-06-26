<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Анализ уязвимостей в зависимостях (мера УКФ.4, ОЦЛ).
 *
 * Запускает `composer audit`. В CI/CD дополните Trivy/OWASP ZAP.
 */
class SecurityAuditDependenciesCommand extends Command
{
    protected $signature = 'security:audit-deps';

    protected $description = 'Проверить зависимости на известные уязвимости (composer audit)';

    public function handle(): int
    {
        $process = new Process(['composer', 'audit', '--no-interaction'], base_path());
        $process->setTimeout(300);
        $process->run(fn ($type, $buffer) => $this->output->write($buffer));

        if (! $process->isSuccessful()) {
            $this->error('Обнаружены уязвимости в зависимостях. Требуется обновление.');

            return self::FAILURE;
        }

        $this->info('Уязвимостей в зависимостях не обнаружено.');

        return self::SUCCESS;
    }
}

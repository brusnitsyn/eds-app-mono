<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class PythonCertificateParserService
{
    public function parse(string $certificatePath): array
    {
        $scriptPath = $this->resolveScriptPath();

        if (!is_file($scriptPath)) {
            throw new RuntimeException("Python certificate parser script not found: {$scriptPath}");
        }

        $process = new Process([
            config('services.certificate_parser.python_binary', 'python3'),
            $scriptPath,
            '--input-file',
            $certificatePath,
            '--output-format',
            'json',
        ], base_path());

        $process->setTimeout((float) config('services.certificate_parser.timeout', 30));
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput() ?: $process->getOutput());

            throw new RuntimeException("Python certificate parser failed: {$errorOutput}");
        }

        $parsed = json_decode($process->getOutput(), true);
        if (!is_array($parsed)) {
            throw new RuntimeException('Python certificate parser returned invalid JSON');
        }

        return $parsed;
    }

    private function resolveScriptPath(): string
    {
        $configuredPath = config('services.certificate_parser.script_path', 'python-services/certificate_parser.py');

        if (str_starts_with($configuredPath, DIRECTORY_SEPARATOR)) {
            return $configuredPath;
        }

        return base_path($configuredPath);
    }
}

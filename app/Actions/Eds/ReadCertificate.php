<?php

namespace App\Actions\Eds;

use App\Events\CertificateProcessingEvent;
use App\Jobs\ProcessCertificateUpload;
use Illuminate\Bus\Batch;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpZip\ZipFile;

class ReadCertificate
{
    public function readSingle(UploadedFile|File $archiveFile): void
    {
        // Извлекаем архив во временную директорию
        $extractedCertificatePath = $this->extractToTemp($archiveFile);

        $jobs = [];
        // Отправляем Job в очередь
        $jobs[] = new ProcessCertificateUpload($extractedCertificatePath);

        if (count($jobs) > 0) {
            $batch = \Bus::batch($jobs)
                ->before(function (Batch $batch) use ($jobs) {
                    broadcast(new CertificateProcessingEvent('started', 'info', "Количество сертификатов: " . count($jobs)));
                })
                ->progress(function (Batch $batch) {
                    broadcast(new CertificateProcessingEvent('processed', 'warning', "Обработка пакетов: {$batch->processedJobs()} из {$batch->totalJobs}"));
                })
                ->finally(function (Batch $batch) {
                    broadcast(new CertificateProcessingEvent('success', 'success', "Обработка пакетов завершена"));
                });

            $batch->dispatch();
        } else {
            broadcast(new CertificateProcessingEvent('failed', 'error', 'Ошибка при обработке пакета'));
        }
    }

    public function readMany(UploadedFile|File $archiveFile): void
    {
        // Извлекаем все архивы во временную директорию
        $extractedTempPackagesPath = $this->extractToTemp($archiveFile);
        $extractedTempPackages = Storage::disk('temp')->files($extractedTempPackagesPath);

        $jobs = [];
        // Обрабатываем каждый пакет
        foreach ($extractedTempPackages as $package) {
            // Извлекаем содержимое текущего пакета
            $extractedCertificatePath = $this->extractToTemp(storage_path("/app/temp/$package"));

            // Отправляем Job в очередь
            $jobs[] = new ProcessCertificateUpload($extractedCertificatePath);
        }

        if (count($jobs) > 0) {
            $batch = \Bus::batch($jobs)
                ->before(function (Batch $batch) use ($jobs) {
                    broadcast(new CertificateProcessingEvent('started', 'info', "Количество сертификатов в пакете: " . count($jobs)));
                })
                ->progress(function (Batch $batch) {
                    broadcast(new CertificateProcessingEvent('processed', 'warning', "Обработка пакетов: {$batch->processedJobs()} из {$batch->totalJobs}"));
                })
                ->finally(function (Batch $batch) {
                    broadcast(new CertificateProcessingEvent('success', 'success', "Обработка пакетов завершена"));
                });

            $batch->dispatch();
        } else {
            broadcast(new CertificateProcessingEvent('failed', 'error', 'Ошибка при обработке пакета'));
        }
    }

    private function extractToTemp(File|string $archiveFile): string
    {
        $hashPath = hash('md5', basename($archiveFile));
        $tempPath = storage_path("/app/temp/$hashPath");

        if (Storage::disk('temp')->exists($hashPath)) {
            Storage::disk('temp')->deleteDirectory($hashPath);
        }
        Storage::disk('temp')->makeDirectory($hashPath);

        $zipTool = new ZipFile();
        $zipTool->openFile($archiveFile);
        $zipTool->extractTo($tempPath);
        $zipTool->close();

        return $hashPath;
    }
}

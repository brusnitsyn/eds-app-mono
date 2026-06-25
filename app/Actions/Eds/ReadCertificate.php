<?php

namespace App\Actions\Eds;

use App\Events\CertificateProcessingEvent;
use App\Jobs\ProcessCertificateUpload;
use Illuminate\Bus\Batch;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpZip\ZipFile;

class ReadCertificate
{
    /**
     * Принимает файлы выбранных папок сертификатов как есть (без ручной упаковки
     * в zip), восстанавливает их структуру во временной директории и запускает
     * обработку. Количество пакетов определяется автоматически по числу файлов
     * сертификата (.cer): один .cer — один сертификат, несколько — пакет.
     *
     * @param array<int, UploadedFile> $files Загруженные файлы
     * @param array<int, string> $paths Относительные пути файлов (выровнены по индексу с $files)
     */
    public function readUploadedFiles(array $files, array $paths): void
    {
        // Сопоставляем каждый файл с его относительным путём внутри выбранной папки.
        $items = [];
        foreach ($files as $i => $file) {
            $relativePath = $this->normalizeRelativePath($paths[$i] ?? $file->getClientOriginalName());
            if ($relativePath === '') {
                continue;
            }
            $items[] = ['file' => $file, 'path' => $relativePath];
        }

        if (empty($items)) {
            broadcast(new CertificateProcessingEvent('failed', 'error', 'Не удалось прочитать загруженные файлы'));
            return;
        }

        // Обратная совместимость: если загрузили один .zip — распаковываем его по старому пути.
        if (count($items) === 1 && strtolower($items[0]['file']->getClientOriginalExtension()) === 'zip') {
            $this->readSingle($items[0]['file']);
            return;
        }

        // Корень пакета — директория, в которой лежит файл сертификата (.cer).
        $packageRoots = [];
        foreach ($items as $item) {
            if (strtolower(pathinfo($item['path'], PATHINFO_EXTENSION)) === 'cer') {
                $packageRoots[] = $this->directoryOf($item['path']);
            }
        }
        $packageRoots = array_values(array_unique($packageRoots));

        if (empty($packageRoots)) {
            broadcast(new CertificateProcessingEvent('failed', 'error', 'В загруженных папках не найден файл сертификата (.cer)'));
            return;
        }

        // Самый длинный (наиболее конкретный) корень имеет приоритет при вложенности папок.
        usort($packageRoots, fn ($a, $b) => strlen($b) <=> strlen($a));

        // Раскладываем файлы по пакетам (каждый файл — в наиболее подходящий корень).
        $packages = [];
        foreach ($items as $item) {
            $root = $this->matchPackageRoot($item['path'], $packageRoots);
            if ($root === null) {
                continue;
            }
            $packages[$root][] = [
                'inner' => $this->stripPrefix($item['path'], $root),
                'file' => $item['file'],
            ];
        }

        // Каждый пакет — в собственную временную директорию + отдельный Job.
        // Подпись пакета — имя его папки (как в CreateStaffForm.vue), чтобы
        // на фронте можно было соотнести строки прогресса с конкретным сертификатом.
        $jobs = [];
        $index = 0;
        foreach ($packages as $root => $packageFiles) {
            $index++;
            $extractedCertificatePath = $this->writePackageToTemp($packageFiles);
            $label = $root !== '' ? basename($root) : "Сертификат {$index}";
            $jobs[] = new ProcessCertificateUpload($extractedCertificatePath, (string) Str::uuid(), $label);
        }

        $this->dispatchBatch($jobs);
    }

    /**
     * Восстанавливает один пакет (сертификат + контейнер) во временной
     * директории, сохраняя относительную структуру файлов.
     */
    private function writePackageToTemp(array $packageFiles): string
    {
        $hashPath = hash('md5', uniqid('', true));

        if (Storage::disk('temp')->exists($hashPath)) {
            Storage::disk('temp')->deleteDirectory($hashPath);
        }
        Storage::disk('temp')->makeDirectory($hashPath);

        foreach ($packageFiles as $packageFile) {
            $inner = $packageFile['inner'];
            $directory = $this->directoryOf($inner);
            $targetDirectory = $directory === '' ? $hashPath : "$hashPath/$directory";

            Storage::disk('temp')->putFileAs($targetDirectory, $packageFile['file'], basename($inner));
        }

        return $hashPath;
    }

    /**
     * Очищает относительный путь: нормализует разделители и убирает попытки
     * выхода за пределы директории (path traversal).
     */
    private function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function directoryOf(string $path): string
    {
        $directory = dirname($path);

        return $directory === '.' ? '' : $directory;
    }

    private function matchPackageRoot(string $path, array $packageRoots): ?string
    {
        foreach ($packageRoots as $root) {
            if ($root === '' || str_starts_with($path, $root . '/')) {
                return $root;
            }
        }

        return null;
    }

    private function stripPrefix(string $path, string $root): string
    {
        if ($root === '') {
            return $path;
        }

        return ltrim(substr($path, strlen($root)), '/');
    }

    public function readSingle(UploadedFile|File $archiveFile): void
    {
        // Извлекаем архив во временную директорию
        $extractedCertificatePath = $this->extractToTemp($archiveFile);

        // Отправляем Job в очередь
        $jobs = [new ProcessCertificateUpload($extractedCertificatePath, (string) Str::uuid())];

        $this->dispatchBatch($jobs);
    }

    public function readMany(UploadedFile|File $archiveFile): void
    {
        // Извлекаем все архивы во временную директорию
        $extractedTempPackagesPath = $this->extractToTemp($archiveFile);
        $extractedTempPackages = Storage::disk('temp')->files($extractedTempPackagesPath);

        $jobs = [];
        $index = 0;
        // Обрабатываем каждый пакет
        foreach ($extractedTempPackages as $package) {
            $index++;
            // Извлекаем содержимое текущего пакета
            $extractedCertificatePath = $this->extractToTemp(storage_path("/app/temp/$package"));

            // Отправляем Job в очередь
            $jobs[] = new ProcessCertificateUpload($extractedCertificatePath, (string) Str::uuid(), "Сертификат {$index}");
        }

        $this->dispatchBatch($jobs);
    }

    /**
     * Ставит обработку пакетов в очередь одним батчем. Каждый сертификат пакета —
     * независимый Job: провал одного не отменяет и не останавливает обработку
     * остальных (Laravel сам по себе лишь помечает батч как "cancelled" — это не
     * мешает уже поставленным в очередь job'ам выполниться, см. Batch::add()).
     * Прогресс и итог транслируются по каждому сертификату отдельно через
     * ProcessCertificateUpload::broadcastStage() — здесь только объявляем
     * фронту состав пакета сразу после постановки в очередь, чтобы не ждать
     * первое событие конкретного сертификата для отображения списка.
     *
     * @param array<int, ProcessCertificateUpload> $jobs
     */
    private function dispatchBatch(array $jobs): void
    {
        if (empty($jobs)) {
            broadcast(new CertificateProcessingEvent('failed', 'error', 'Ошибка при обработке пакета'));
            return;
        }

        \Bus::batch($jobs)
            ->before(function (Batch $batch) use ($jobs) {
                broadcast(new CertificateProcessingEvent('started', 'info', "Количество сертификатов: " . count($jobs), null, [
                    'packages' => array_map(
                        fn (ProcessCertificateUpload $job) => ['id' => $job->packageId, 'label' => $job->packageLabel],
                        $jobs
                    ),
                ]));
            })
            ->dispatch();
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

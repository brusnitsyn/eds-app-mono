<?php

namespace App\Actions\Eds;

use App\Facades\Crypto;
use App\Models\Staff;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpZip\ZipFile;

class ReadCertificate
{
    private function bcdechex($dec) {
        $last = bcmod($dec, 16);
        $remain = bcdiv(bcsub($dec, $last), 16);
        if($remain == 0) {
            return dechex($last);
        } else {
            return $this->bcdechex($remain).dechex($last);
        }
    }

    public function read(string $pathToCertificate): array
    {
        $certificateDir = pathinfo($pathToCertificate, PATHINFO_DIRNAME);
        $closeKeyValidTo = null;

        // Поиск всех header.key файлов
        $closeKeyFiles = glob($certificateDir . '/*/header.key');
        foreach ($closeKeyFiles as $closeKeyFile) {
            try {
                $closeKeyContent = file_get_contents($closeKeyFile);
                $pattern = '/\d{14}Z/';
                if (preg_match($pattern, $closeKeyContent, $matches)) {
                    $dateString = $matches[0];
                    $closeKeyValidTo = Carbon::parse($dateString)->getTimestampMs();
                    break; // Если нашли дату, выходим из цикла
                }
            } catch (\Exception $e) {
                Log::error("Ошибка при чтении файла: $closeKeyFile", ['error' => $e->getMessage()]);
            }
        }

        $certContents = file_get_contents($pathToCertificate);
        $certificateCAPemContent = '-----BEGIN CERTIFICATE-----' . PHP_EOL
            . chunk_split(base64_encode($certContents), 64, PHP_EOL)
            . '-----END CERTIFICATE-----' . PHP_EOL;

        $parsedCert = openssl_x509_parse($certificateCAPemContent);

        $serialNumber = $parsedCert['serialNumber'];
        if (intval($serialNumber)) {
            $serialNumber = strtoupper($this->bcdechex($parsedCert['serialNumber']));
            if (strlen($serialNumber) % 2 !== 0) {
                $serialNumber = '0' . $serialNumber;
            }
        }
        if (Str::contains($serialNumber, '0x')) {
            $serialNumber = Str::replace('0x', '00', $serialNumber);
        }

        $parsedSubject = $parsedCert['subject'];
        $full_name = $parsedSubject['CN'];
        $explodeFullName = Str::of($full_name)->explode(' ');
        $first_name = $explodeFullName[1];
        $middle_name = $explodeFullName[2];
        $last_name = $explodeFullName[0];

        $job_title = Str::ucfirst(Str::lower($parsedSubject['title'] ?? ''));

        return [
            'certificate' => [
                'serial_number' => $serialNumber,
                'valid_from' => Carbon::parse($parsedCert['validFrom_time_t'])->getTimestampMs(),
                'valid_to' => Carbon::parse($parsedCert['validTo_time_t'])->getTimestampMs(),
                'close_key_valid_to' => $closeKeyValidTo
            ],
            'job_title' => $job_title,
            'full_name' => $full_name,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'snils' => $parsedSubject['SNILS'],
            'inn' => $parsedSubject['INN']
        ];
    }

    public function readSingle(UploadedFile|File $archiveFile): void
    {
        // Извлекаем архив во временную директорию
        $extractedCertificatePath = $this->extractToTemp($archiveFile);
        $extractedFiles = Storage::disk('temp')->allFiles($extractedCertificatePath);

        // Ищем файл сертификата (.cer)
        $cerFile = collect($extractedFiles)->first(function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'cer';
        });

        if (!$cerFile) {
            throw new \Exception('Не найден файл сертификата (.cer)');
        }

        // Читаем информацию из сертификата
        $certContents = file_get_contents(Storage::disk('temp')->path($cerFile));
        $certificateCAPemContent = '-----BEGIN CERTIFICATE-----' . PHP_EOL
            . chunk_split(base64_encode($certContents), 64, PHP_EOL)
            . '-----END CERTIFICATE-----' . PHP_EOL;

        $parsedCert = openssl_x509_parse($certificateCAPemContent);
        $validToTimestamp = Carbon::parse($parsedCert['validTo_time_t'])->getTimestampMs();
        $snils = $parsedCert['subject']['SNILS']; // Получаем СНИЛС пользователя из сертификата

        $staff = Staff::whereSnils($snils)->first();
        if ($staff && $staff->certification->valid_to > $validToTimestamp) {
            return; // Пропускаем пакет, так как сертификат устаревший
        }

        // Короткое имя папки
        $folderName = hash('md5', $snils);

        // Создаем папку с именем пользователя
        $destinationPath = "$folderName";
        $this->moveDirectoryToStorage($extractedCertificatePath, $destinationPath);

        // Получаем путь к файлу сертификата в постоянном хранилище
        $certificateFile = Storage::disk('certification')->path("$destinationPath/" . basename($cerFile));

        // Читаем информацию из сертификата
        $certificationInfo = $this->read($certificateFile);

        // Добавляем путь к сертификату и ключам в результат
        $certificationInfo['certificate']['path_certification'] = $destinationPath;
        $certificationInfo['certificate']['file_certification'] = basename($cerFile);

        // Сохраняем информацию в базу данных
        DB::transaction(function () use ($certificationInfo) {
            $createdStaff = new CreateNewStaff();
            $createdStaff->create($certificationInfo);
        });

        // Шифруем все файлы в папке
        $this->encryptFilesInDirectory($destinationPath);

        // Удаляем временные файлы
        Storage::disk('temp')->deleteDirectory($extractedCertificatePath);
    }

    public function readMany(UploadedFile|File $archiveFile): void
    {
        // Извлекаем все архивы во временную директорию
        $extractedTempPackagesPath = $this->extractToTemp($archiveFile);
        $extractedTempPackages = Storage::disk('temp')->files($extractedTempPackagesPath);

        // Обрабатываем каждый пакет
        foreach ($extractedTempPackages as $package) {
            Log::debug("Начато чтение пакета: $package");

            // Извлекаем содержимое текущего пакета
            $extractedCertificatePath = $this->extractToTemp(storage_path("/app/temp/$package"));
            $extractedFiles = Storage::disk('temp')->allFiles($extractedCertificatePath);

            // Ищем файл сертификата (.cer)
            $cerFile = collect($extractedFiles)->first(function ($file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'cer';
            });

            if (!$cerFile) {
                Log::error("Не найден файл сертификата (.cer) в пакете: $package");
                continue; // Пропускаем пакет, если нет сертификата
            }

            // Читаем информацию из сертификата
            $certContents = file_get_contents(Storage::disk('temp')->path($cerFile));
            $certificateCAPemContent = '-----BEGIN CERTIFICATE-----' . PHP_EOL
                . chunk_split(base64_encode($certContents), 64, PHP_EOL)
                . '-----END CERTIFICATE-----' . PHP_EOL;

            $parsedCert = openssl_x509_parse($certificateCAPemContent);
            $validToTimestamp = Carbon::parse($parsedCert['validTo_time_t'])->getTimestampMs();
            $snils = $parsedCert['subject']['SNILS']; // Получаем СНИЛС пользователя из сертификата

            $staff = Staff::whereSnils($snils)->first();
            if ($staff && $staff->certification->valid_to > $validToTimestamp) {
                continue; // Пропускаем пакет, так как сертификат устаревший
            }

            // Короткое имя папки
            $folderName = hash('md5', $snils);

            // Создаем папку с именем пользователя
            $destinationPath = "$folderName";
            $this->moveDirectoryToStorage($extractedCertificatePath, $destinationPath);

            // Получаем путь к файлу сертификата в постоянном хранилище
            $certificateFile = Storage::disk('certification')->path("$destinationPath/" . basename($cerFile));

            // Читаем информацию из сертификата
            $certificationInfo = $this->read($certificateFile);

            // Добавляем путь к сертификату и ключам в результат
            $certificationInfo['certificate']['path_certification'] = $destinationPath;
            $certificationInfo['certificate']['file_certification'] = basename($cerFile);

            // Сохраняем информацию в базу данных
            DB::transaction(function () use ($certificationInfo) {
                $createdStaff = new CreateNewStaff();
                $createdStaff->create($certificationInfo);
            });

            // Шифруем все файлы в папке
            $this->encryptFilesInDirectory($destinationPath);
        }

        // Удаляем временные файлы
        Storage::disk('temp')->deleteDirectory($extractedTempPackagesPath);
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

    private function moveDirectoryToStorage(string $sourcePath, string $destinationPath): void
    {
        // Удаляем старую папку, если она существует
        if (Storage::disk('certification')->exists($destinationPath)) {
            Storage::disk('certification')->deleteDirectory($destinationPath);
        }

        // Создаем новую папку
        Storage::disk('certification')->makeDirectory($destinationPath);

        // Перемещаем все файлы и подпапки
        $files = Storage::disk('temp')->allFiles($sourcePath);
        foreach ($files as $file) {
            // Убираем лишнюю часть пути (например, "temp/")
            $relativePath = str_replace($sourcePath . '/', '', $file);
            $destinationFile = "$destinationPath/$relativePath";

            // Создаем подпапки, если они есть
            $destinationDir = dirname($destinationFile);
            if (!Storage::disk('certification')->exists($destinationDir)) {
                Storage::disk('certification')->makeDirectory($destinationDir);
            }

            // Перемещаем файл
            Storage::disk('certification')->put($destinationFile, Storage::disk('temp')->get($file));
        }
    }

    private function encryptFilesInDirectory(string $directory)
    {
        $files = Storage::disk('certification')->allFiles($directory);
        foreach ($files as $file) {
            Crypto::encryptFile(Storage::disk('certification')->path($file));
        }
    }
}

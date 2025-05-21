<?php

namespace App\Jobs;

use App\Actions\Eds\CreateNewStaff;
use App\Events\CertificateProcessingEvent;
use App\Facades\Crypto;
use App\Models\Staff;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpZip\ZipFile;

class ProcessCertificateUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected $extractedCertificatePath;

    public function __construct($extractedCertificatePath)
    {
        $this->extractedCertificatePath = $extractedCertificatePath;
    }

    public function handle(): void
    {
        $extractedFiles = Storage::disk('temp')->allFiles($this->extractedCertificatePath);

        // Ищем файл сертификата (.cer)
        $cerFile = collect($extractedFiles)->first(function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'cer';
        });

        if (!$cerFile) {
            Log::error('Не найден файл сертификата (.cer)');
            return;
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
            Log::info('Сертификат устаревший, пропускаем пакет');
            return; // Пропускаем пакет, так как сертификат устаревший
        }

        // Короткое имя папки
        $folderName = hash('md5', $snils);

        // Создаем папку с именем пользователя
        $destinationPath = "$folderName";
        $this->moveDirectoryToStorage($this->extractedCertificatePath, $destinationPath);

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
        Storage::disk('temp')->deleteDirectory($this->extractedCertificatePath);
    }

    private function moveDirectoryToStorage($sourcePath, $destinationPath): void
    {
        if (Storage::disk('certification')->exists($destinationPath)) {
            Storage::disk('certification')->deleteDirectory($destinationPath);
        }
        Storage::disk('certification')->makeDirectory($destinationPath);

        $files = Storage::disk('temp')->allFiles($sourcePath);
        foreach ($files as $file) {
            $relativePath = str_replace($sourcePath . '/', '', $file);
            $destinationFile = "$destinationPath/$relativePath";
            Storage::disk('certification')->put($destinationFile, Storage::disk('temp')->get($file));
        }
    }

    private function encryptFilesInDirectory($directory): void
    {
        $files = Storage::disk('certification')->allFiles($directory);
        foreach ($files as $file) {
            Crypto::encryptFile(Storage::disk('certification')->path($file));
        }
    }

    private function read($certificateFile): array
    {
        $certificateDir = pathinfo($certificateFile, PATHINFO_DIRNAME);
        $closeKeyValidTo = null;

        // Поиск всех header.key файлов
        $closeKeyFiles = glob($certificateDir . '/*/header.key');
        foreach ($closeKeyFiles as $closeKeyFile) {
            try {
                $closeKeyContent = file_get_contents($closeKeyFile);
                $pattern = '/\d{14}Z/';
                $patternNew = '/\d{12}Z0/';
                if (preg_match($patternNew, $closeKeyContent, $matches)) {
                    $dateString = $matches[0];
                    $closeKeyValidTo = Carbon::parse($dateString)->getTimestampMs();
                    break; // Если нашли дату, выходим из цикла
                }
                if (preg_match($pattern, $closeKeyContent, $matches)) {
                    $dateString = $matches[0];
                    $closeKeyValidTo = Carbon::parse($dateString)->getTimestampMs();
                    break; // Если нашли дату, выходим из цикла
                }
            } catch (\Exception $e) {
                Log::error("Ошибка при чтении файла: $closeKeyFile", ['error' => $e->getMessage()]);
            }
        }

        $certContents = file_get_contents($certificateFile);
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

    private function bcdechex($dec): string
    {
        $last = bcmod($dec, 16);
        $remain = bcdiv(bcsub($dec, $last), 16);
        if($remain == 0) {
            return dechex($last);
        } else {
            return $this->bcdechex($remain).dechex($last);
        }
    }
}

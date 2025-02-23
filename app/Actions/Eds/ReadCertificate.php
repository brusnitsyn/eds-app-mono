<?php

namespace App\Actions\Eds;

use App\Facades\Crypto;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
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

    public function read(string $pathToCertificate)
    {
        $certificateDir = pathinfo($pathToCertificate, PATHINFO_DIRNAME);
        $closeKeyFolder = glob($certificateDir . '/*', GLOB_ONLYDIR);

        $closeKeyValidTo = null;
        if (count($closeKeyFolder) && $closeKeyFolder[0]) {
            $closeKeyPath = "$closeKeyFolder[0]/header.key";
            $closeKeyContent = file_get_contents($closeKeyPath);
            // Регулярное выражение для поиска даты в формате YYYYMMDDHHMMSSZ
            $pattern = '/\d{14}Z/';
            if (preg_match($pattern, $closeKeyContent, $matches)) {
                $dateString = $matches[0];
                $closeKeyValidTo = Carbon::parse($dateString)->getTimestampMs();
            }
        }

        $certContents = file_get_contents($pathToCertificate);

        $certificateCAPemContent = '-----BEGIN CERTIFICATE-----'.PHP_EOL
            .chunk_split(base64_encode($certContents), 64, PHP_EOL)
            .'-----END CERTIFICATE-----'.PHP_EOL;

        $parsedCert = openssl_x509_parse($certificateCAPemContent);

        $serialNumber = $parsedCert['serialNumber'];

        if(intval($serialNumber)) {
            $serialNumber = strtoupper($this->bcdechex($parsedCert['serialNumber']));
            if (strlen($serialNumber) % 2 !== 0) {
                $serialNumber = '0' . $serialNumber;
            }
        }
        if(Str::contains($serialNumber, '0x')) $serialNumber = Str::replace('0x', '00', $serialNumber);

        $parsedSubject = $parsedCert['subject'];

        $full_name = $parsedSubject['CN'];
        $explodeFullName = Str::of($full_name)->explode(' ');
        $first_name = $explodeFullName[1];
        $middle_name = $explodeFullName[2];
        $last_name = $explodeFullName[0];

        $job_title = Str::ucfirst(Str::lower($parsedSubject['title'] ?? ''));

        $result = [
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

        return $result;
    }

    public function readSingle(UploadedFile|File $archiveFile)
    {
        $extractedCertificatePath = $this->extractToTemp($archiveFile);
        $extractedFiles = Storage::disk('temp')->files($extractedCertificatePath);

        $cerFileName = null;

        foreach ($extractedFiles as $extractedFile) {
            if (pathinfo($extractedFile, PATHINFO_EXTENSION) == "cer") {
                $cerFileName = pathinfo($extractedFile, PATHINFO_FILENAME);
            }
        }

        if (!$cerFileName) {
            // TODO: Обработать ошибку, если нет открытого ключа
        }

        foreach ($extractedFiles as $extractedFile) {
            if (Storage::disk('certification')->exists($cerFileName))
                Storage::disk('certification')->deleteDirectory($cerFileName);

            $extractedDirectory = pathinfo($extractedFile, PATHINFO_DIRNAME);
            $this->moveToStorage("$extractedDirectory", "$cerFileName");
        }

        $certificateFiles = Storage::disk('certification')->files($cerFileName);
        $certificateFile = null;
        $certificateDirectory = null;
        foreach ($certificateFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == "cer") {
                $certificateDirectory = pathinfo($file, PATHINFO_DIRNAME);
                $certificateFile = pathinfo($file, PATHINFO_FILENAME) . '/' . pathinfo($file, PATHINFO_BASENAME);
            }
        }

        $certificationInfo = $this->read(Storage::disk('certification')->path($certificateFile));

        $certificationInfo['certificate']['path_certification'] = $certificateDirectory;
        $certificationInfo['certificate']['file_certification'] = $certificateFile;

        $createdStaff = new CreateNewStaff();
        $createdStaff->create($certificationInfo);

        Storage::disk('temp')->deleteDirectory($extractedCertificatePath);

        $certificateFiles = collect(Storage::disk('certification')->files($cerFileName, true));
        $certificateFiles = $certificateFiles->map(function ($path) {
            return Storage::disk('certification')->path($path);
        });
        $certificateFiles = $certificateFiles->all();

        foreach ($certificateFiles as $file) {
            Crypto::encryptFile($file);
        }
    }

    public function readMany(UploadedFile|File $archiveFile)
    {
        $extractedTempPackagesPath = $this->extractToTemp($archiveFile);
        $extractedTempPackages = Storage::disk('temp')->files($extractedTempPackagesPath);

        foreach ($extractedTempPackages as $package) {
            $extractedCertificatePath = $this->extractToTemp(storage_path("/app/temp/$package"));
            $extractedFiles = Storage::disk('temp')->files($extractedCertificatePath);
            $cerFileName = null;

            foreach ($extractedFiles as $extractedFile) {
                if (pathinfo($extractedFile, PATHINFO_EXTENSION) == "cer") {
                    $cerFileName = pathinfo($extractedFile, PATHINFO_FILENAME);
                }
            }

            if (!$cerFileName) {
                // TODO: Обработать ошибку, если нет открытого ключа
            }

            foreach ($extractedFiles as $extractedFile) {
                if (Storage::disk('certification')->exists($cerFileName))
                    Storage::disk('certification')->deleteDirectory($cerFileName);

                $extractedDirectory = pathinfo($extractedFile, PATHINFO_DIRNAME);
                $this->moveToStorage("$extractedDirectory", "$cerFileName");
            }

            $certificateFiles = Storage::disk('certification')->files($cerFileName);
            $certificateFile = null;
            $certificateDirectory = null;
            foreach ($certificateFiles as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) == "cer") {
                    $certificateDirectory = pathinfo($file, PATHINFO_DIRNAME);
                    $certificateFile = pathinfo($file, PATHINFO_FILENAME) . '/' . pathinfo($file, PATHINFO_BASENAME);
                }
            }

            $certificationInfo = $this->read(Storage::disk('certification')->path($certificateFile));

            $certificationInfo['certificate']['path_certification'] = $certificateDirectory;
            $certificationInfo['certificate']['file_certification'] = $certificateFile;

            $createdStaff = new CreateNewStaff();
            $createdStaff->create($certificationInfo);
        }

        Storage::disk('temp')->deleteDirectory($extractedTempPackagesPath);
    }

    private function extractToTemp(File|string $archiveFile)
    {
        $pathInfo = pathinfo($archiveFile);
        $hashPath = hash('md5', $pathInfo['basename']);
        $tempPath = storage_path("/app/temp/$hashPath");
        $zipTool = new ZipFile();
        $zipTool->openFile($archiveFile);

        if (Storage::disk('temp')->exists($hashPath)) {
            Storage::disk('temp')->deleteDirectory($hashPath);
            Storage::disk('temp')->makeDirectory($hashPath);
        } else {
            Storage::disk('temp')->makeDirectory($hashPath);
        }

        $zipTool->extractTo($tempPath);
        $zipTool->close();

        return $hashPath;
    }

    private function extractToStorage(string $path)
    {
        $pathInfo = pathinfo($path);
        $baseName = $pathInfo['basename'];
        if (Storage::disk('certification')->exists($baseName)) {
            Storage::disk('certification')->deleteDirectory($baseName);
        }

        Storage::disk('certification')->makeDirectory($baseName);

        $zipTool = new ZipFile();
        $zipTool->openFile($path);
        $zipTool->extractTo(storage_path("/app/certification/$baseName"));
        $zipTool->close();

        return $baseName;
    }

    private function moveToStorage(string $path, string $newPath)
    {
        \Illuminate\Support\Facades\File::moveDirectory(storage_path("/app/temp/$path"), storage_path("/app/certifications/$newPath"));
    }
}

<?php

namespace App\Imports;

use App\Data\Mis\Import\DoctorImportData;
use App\Events\CertificateProcessingEvent;
use App\Jobs\MisCreateImportedAccountJob;
use Illuminate\Bus\Batch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Npub\Gos\Snils;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class MisDoctorImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $jobs = $rows->map(function ($row) {
            $snilsCanonical = Snils::stringFormat($row->get('snils'));

            if (Snils::validate($snilsCanonical) !== null) {
                $data = DoctorImportData::from([
                    ...$row,
                    'data_rozdeniia' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row->get('data_rozdeniia')), // Конвертация Excel-даты
                ]);
                return new MisCreateImportedAccountJob($data);
            }

            return null;
        });

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
    }

    public function headingRow(): int
    {
        return 1;
    }
}

<?php

namespace App\Imports;

use App\Data\Mis\Import\DoctorImportData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class MisDoctorFirstImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'LIST' => new MisDoctorImport(), // Явно указываем первый лист (индекс 0)
        ];
    }
}

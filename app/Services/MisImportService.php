<?php

namespace App\Services;

use App\Imports\MisDoctorFirstImport;
use App\Imports\MisDoctorImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class MisImportService
{
    public function doctors(UploadedFile $file)
    {
        $import = new MisDoctorFirstImport();
        Excel::import($import, $file);
    }
}

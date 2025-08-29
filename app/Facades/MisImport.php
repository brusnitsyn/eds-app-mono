<?php

namespace App\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;

/**
 * @method static doctors(UploadedFile $file)
 *
 * @see \App\Services\MisImportService
 */
class MisImport extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'misimport.facade';
    }
}

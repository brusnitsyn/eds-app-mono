<?php

namespace App\Facades;

use App\Data\Mis\LpuDoctorData;
use Illuminate\Support\Facades\Facade;

/**
 * @method static getPaginate(string|null $searchValue, int $pageSize)
 * @method static int countDoctors(string|null $searchValue)
 * @method static \Illuminate\Support\Collection getSlice(string|null $searchValue, int $offset, int $limit)
 * @method static LpuDoctorData getDoctorById(int $id)
 * @method static LpuDoctorData getDoctorByPcod(string $pcod)
 * @method static LpuDoctorData createDoctor(array $data)
 *
 * @see \App\Services\MisDoctorService
 */
class MisDoctor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'misdoctor.facade';
    }
}

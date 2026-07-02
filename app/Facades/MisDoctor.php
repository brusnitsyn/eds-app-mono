<?php

namespace App\Facades;

use App\Data\Mis\DoctorData;
use App\Data\Mis\PrvdData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static LengthAwarePaginator getPaginate(string|null $searchValue, int $pageSize)
 * @method static int countDoctors(string|null $searchValue)
 * @method static Collection getSlice(string|null $searchValue, int $offset, int $limit)
 * @method static DoctorData getDoctorById(int $id)
 * @method static DoctorData getDoctorByPcod(string $pcod)
 * @method static DoctorData getDoctorByGuid(string $guid)
 * @method static DoctorData createDoctor(DoctorData $data, bool $hasCreatePrvd = false)
 * @method static void updateDoctor(int $id, DoctorData $data)
 * @method static Collection getPrvd(int $doctorId)
 * @method static PrvdData createPrvd(PrvdData $data)
 * @method static void updatePrvd(PrvdData $data)
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

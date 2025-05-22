<?php

namespace App\Facades;

use App\Data\Mis\XUserMis;
use Illuminate\Support\Facades\Facade;

/**
 * @method static XUserMis|null getUserById(int $userId)
 * @method static XUserMis|null getUserByDoctorId(int $doctorId)
 * @method static XUserMis|null getUserByDoctorPcod(string $doctorPcod)
 *
 * @see \App\Services\MisXUserService
 */
class MisXUser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'misxuser.facade';
    }
}

<?php

namespace App\Facades;

use App\Data\Mis\XUserMis;
use Illuminate\Support\Facades\Facade;

/**
 * @method static XUserMis|null getUserById(int $userId)
 * @method static XUserMis|null getUserByGuid(string $guid)
 * @method static XUserMis|null getUserByDoctorId(int $doctorId)
 * @method static XUserMis|null getUserByDoctorPcod(string $pcod)
 * @method static XUserMis|null createUser(array $data)
 * @method static bool assignToDoctor(XUserMis $user, string $doctorCode)
 * @method static void updateCredentials(int $userId, string $login, string $password)
 * @method static void changePassword(int $xUserId)
 * @method static string formatedLogin(string $fam, string $ot, string $im)
 * @method static string computeHash(string $password, string $guid)
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

<?php

namespace App\Services;

use App\Data\Mis\XUserMis;
use App\Models\Mis\XUser;
use App\Models\Mis\XUserSettings;
use App\Models\MisLPUDoctorToUserID;
use App\Models\MisPasswordHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class MisXUserService
{
    public function getUserById(int $userId): ?XUserMis
    {
        $user = XUser::find($userId);
        return $user ? XUserMis::from($user->toArray()) : null;
    }

    public function getUserByGuid(string $guid): ?XUserMis
    {
        $user = XUser::where('GUID', $guid)->first();
        return $user ? XUserMis::from($user->toArray()) : null;
    }

    public function getUserByDoctorId(int $doctorId): ?XUserMis
    {
        $mapping = MisLPUDoctorToUserID::where('lpu_doctor_id', $doctorId)->first();

        if ($mapping) {
            $xUser = $this->getUserById($mapping->user_id);
        } else {
            $doctor = XUser::getConnection()->table('hlt_LPUDoctor')
                ->select('PCOD')
                ->where('LPUDoctorID', $doctorId)
                ->first();

            $xUser = $doctor ? $this->getUserByDoctorPcod($doctor->PCOD) : null;
        }

        if ($xUser) {
            MisLPUDoctorToUserID::updateOrCreate(
                ['lpu_doctor_id' => $doctorId],
                ['user_id' => $xUser->UserID, 'lpu_doctor_id' => $doctorId]
            );
        }

        return $xUser;
    }

    public function getUserByDoctorPcod(string $pcod): ?XUserMis
    {
        $user = XUser::join('x_UserSettings', 'x_UserSettings.rf_UserID', '=', 'x_User.UserID')
            ->where('x_UserSettings.Property', 'Код врача')
            ->where('x_UserSettings.ValueStr', $pcod)
            ->select(['x_User.UserID', 'x_User.GeneralLogin', 'x_User.GeneralPassword', 'x_User.FIO', 'x_User.GUID', 'x_User.AuthMode'])
            ->first();

        return $user ? XUserMis::from($user->toArray()) : null;
    }

    public function createUser(array $data): ?XUserMis
    {
        $guid = Str::uuid()->toString();

        $user = XUser::create([
            'GeneralLogin'    => $data['GeneralLogin'],
            'FIO'             => $data['FIO'] ?? '',
            'AuthMode'        => $data['AuthMode'] ?? 1,
            'GUID'            => $guid,
            'GeneralPassword' => $this->computeHash('1234567', $guid),
        ]);

        return $this->getUserById($user->UserID);
    }

    public function assignToDoctor(XUserMis $user, string $doctorCode): bool
    {
        $base = ['OwnerGUID' => $user->GUID, 'DocTypeDefGUID' => Uuid::NIL, 'rf_UserID' => $user->UserID];

        XUserSettings::create(array_merge($base, [
            'Property'        => 'Код врача',
            'ValueStr'        => $doctorCode,
            'rf_SettingTypeID'=> 7,
        ]));

        XUserSettings::create(array_merge($base, [
            'Property'        => 'Автоопределение врача',
            'ValueInt'        => 1,
            'rf_SettingTypeID'=> 8,
        ]));

        XUserSettings::create(array_merge($base, [
            'Property'        => 'Использование формы мед. документа',
            'ValueInt'        => 1,
            'rf_SettingTypeID'=> 8,
        ]));

        return true;
    }

    public function updateCredentials(int $userId, string $login, string $password): void
    {
        $user = XUser::findOrFail($userId);
        $user->update([
            'GeneralLogin'    => $login,
            'GeneralPassword' => $this->computeHash($password, $user->GUID),
        ]);
    }

    /**
     * Переключает пароль: сохраняет текущий в историю и ставит дефолтный,
     * либо восстанавливает оригинальный если история уже есть.
     */
    public function changePassword(int $xUserId): void
    {
        $user = XUser::findOrFail($xUserId);

        $history = MisPasswordHistory::where('user_id', $xUserId)->first();

        if ($history) {
            $user->update(['GeneralPassword' => $history->original_password]);
            $history->delete();
        } else {
            $newPassword = $this->computeHash('1234567', $user->GUID);
            MisPasswordHistory::create([
                'user_id'           => $xUserId,
                'original_password' => $user->GeneralPassword,
                'password'          => $newPassword,
                'guid'              => $user->GUID,
            ]);
            $user->update(['GeneralPassword' => $newPassword]);
        }
    }

    public function formatedLogin(string $fam, string $ot, string $im): string
    {
        return Str::title($fam)
            . Str::upper(Str::take($im, 1) ?: '')
            . Str::upper(Str::take($ot, 1) ?: '');
    }

    public function computeHash(string $password, string $guid): string
    {
        if (!Str::isUuid($guid)) {
            Log::warning('Invalid GUID format provided', ['guid' => $guid]);
            throw new \InvalidArgumentException('The provided GUID is not valid');
        }

        $text  = strtoupper($guid);
        $bytes = $text . $password . $text;
        $hash  = sha1($bytes, true);

        for ($i = 0; $i < 3; $i++) {
            $hash = sha1($hash, true);
        }

        return base64_encode($hash);
    }
}

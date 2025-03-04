<?php

namespace App\Listeners;

use App\Events\CertificationCreated;
use App\Events\CertificationUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckCertificationIdentify
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CertificationCreated|CertificationUpdated $event): void
    {
        $staff = $event->certification->staff;
        $misUserId = $staff->mis_user_id;

        // Получить все настройки пользователя
        $misUserSettings = DB::connection('mis')
            ->table('amu_mis_AOKB_prod.dbo.x_UserSettings')
            ->where('rf_UserID', $misUserId)
            ->get();

        // Получить настройку номера сертификата
        $misUserCertification = $misUserSettings
            ->where('Property', '=', 'Номер сертификата пользователя')
            ->first();

        // Настройка ЕДС - действителен с
        $misUserCertificationValidFrom = $misUserSettings
            ->where('Property', '=', 'Сертификат действителен с')
            ->first();

        // Настройка ЕДС - действителен по
        $misUserCertificationValidTo = $misUserSettings
            ->where('Property', '=', 'Сертификат действителен по')
            ->first();

        $misSerialNumber = $misUserCertification ? $misUserCertification->ValueStr : null;
        $misValidFrom = $misUserCertificationValidFrom ? $misUserCertificationValidFrom->ValueStr : null;
        $misValidTo = $misUserCertificationValidTo ? $misUserCertificationValidTo->ValueStr : null;

        $event->certification->updateQuietly([
            'mis_serial_number' => $misSerialNumber,
            'mis_valid_from' => $misValidFrom,
            'mis_valid_to' => $misValidTo,
            'mis_is_identical' => $misSerialNumber && Str::contains($event->certification->serial_number, $misSerialNumber, true)
        ]);
    }
}

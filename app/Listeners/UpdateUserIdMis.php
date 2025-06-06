<?php

namespace App\Listeners;

use App\Actions\Mis\CheckCertificationIdentify;
use App\Events\StaffCreated;
use App\Events\StaffUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateUserIdMis
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
    public function handle(StaffCreated|StaffUpdated $event): void
    {
        $drivers = collect(DB::availableDrivers());

        if(!$drivers->values()->search('sqlsrv')) {
            return;
        }

        \Log::debug('Начат поиск ID, GeneralLogin');
        $staff = $event->staff;
        $FIO = $staff->full_name;

        try {
            $user = DB::connection('mis')
                ->table('dbo.x_User')
                ->where('FIO', $FIO)
                ->first();

            if ($user) {
                \Log::debug("ID = {$user->UserID}");
                \Log::debug("GeneralLogin = {$user->GeneralLogin}");
                $staff->updateQuietly([
                    'mis_user_id' => $user->UserID,
                    'mis_login' => $user->GeneralLogin,
                ]);
            }
        } catch (\Exception $e) {
            \Log::debug("Пользователь не найден в МИС");
        }
    }
}

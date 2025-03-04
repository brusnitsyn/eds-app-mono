<?php

namespace App\Listeners;

use App\Actions\Mis\CheckCertificationIdentify;
use App\Events\StaffCreated;
use App\Events\StaffUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

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
        \Log::debug('Начат поиск ID');
        $staff = $event->staff;
        $FIO = $staff->full_name;
        $user = DB::connection('mis')
            ->table('dbo.x_User')
            ->where('FIO', $FIO)
            ->first();

        if ($user) {
            \Log::debug("ID = {$user->UserID}");
            $staff->updateQuietly([
                'mis_user_id' => $user->UserID,
            ]);
        }
    }
}

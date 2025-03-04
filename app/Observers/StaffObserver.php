<?php

namespace App\Observers;

use App\Events\StaffCreated;
use App\Events\StaffUpdated;
use App\Models\Staff;

class StaffObserver
{
    /**
     * Handle the Staff "created" event.
     */
    public function created(Staff $staff): void
    {
        StaffCreated::dispatch($staff);
    }

    /**
     * Handle the Staff "updated" event.
     */
    public function updated(Staff $staff): void
    {
        StaffUpdated::dispatch($staff);
    }

    /**
     * Handle the Staff "deleted" event.
     */
    public function deleted(Staff $staff): void
    {
        //
    }

    /**
     * Handle the Staff "restored" event.
     */
    public function restored(Staff $staff): void
    {
        //
    }

    /**
     * Handle the Staff "force deleted" event.
     */
    public function forceDeleted(Staff $staff): void
    {
        //
    }
}

<?php

namespace App\Observers;

use App\Events\CertificationCreated;
use App\Events\CertificationUpdated;
use App\Models\Certification;

class CertificationObserver
{
    /**
     * Handle the Certification "created" event.
     */
    public function created(Certification $certification): void
    {
        CertificationCreated::dispatch($certification);
    }

    /**
     * Handle the Certification "updated" event.
     */
    public function updated(Certification $certification): void
    {
        CertificationUpdated::dispatch($certification);
    }

    /**
     * Handle the Certification "deleted" event.
     */
    public function deleted(Certification $certification): void
    {
        //
    }

    /**
     * Handle the Certification "restored" event.
     */
    public function restored(Certification $certification): void
    {
        //
    }

    /**
     * Handle the Certification "force deleted" event.
     */
    public function forceDeleted(Certification $certification): void
    {
        //
    }
}

<?php

namespace App\Jobs;

use App\Models\Certification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class ServeValidCertificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $certifications = Certification::all();

        foreach ($certifications as $certification) {
            $now = Carbon::now();
            $validTo = Carbon::createFromTimestampMs(intval($certification->valid_to));

            if ($validTo->isFuture()) {
                $certification->update(['is_valid' => true]);
                if ($now->diffInMonths($validTo) < 1)
                    $certification->update(['is_request_new' => true]);
                else
                    $certification->update(['is_request_new' => false]);
            } else {
                $certification->update(['is_valid' => false]);
                $certification->update(['is_request_new' => false]);
            }

        }
    }
}

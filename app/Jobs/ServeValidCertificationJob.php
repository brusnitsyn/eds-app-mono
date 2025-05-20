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
        Certification::chunk(100, function ($certifications) {
            foreach ($certifications as $certification) {
                $validTo = Carbon::createFromTimestampMs(intval($certification->valid_to));
                $validToCloseKey = Carbon::createFromTimestampMs(intval($certification->close_key_valid_to));

                $validToData = $this->checkValid($validTo);
                $validToCloseKeyData = $this->checkValid($validToCloseKey);

                $certification->update([
                    'is_valid' => $validToData['is_valid'] && $validToCloseKeyData['is_valid'],
                    'is_request_new' => $validToData['is_request_new'],
                    'close_key_is_valid' => $validToCloseKeyData['is_valid'],
                    'close_key_is_request_new' => $validToCloseKeyData['is_request_new']
                ]);
            }
        });
    }

    private function checkValid(Carbon $date): array
    {
        $now = Carbon::now();
        if ($date->isFuture()) {
            if ($now->diffInMonths($date) < 1)
                return ['is_valid' => true, 'is_request_new' => true];
            else
                return ['is_valid' => true, 'is_request_new' => false];
        } else {
            return ['is_valid' => false, 'is_request_new' => false];
        }
    }
}

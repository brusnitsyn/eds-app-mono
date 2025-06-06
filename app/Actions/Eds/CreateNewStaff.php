<?php

namespace App\Actions\Eds;

use App\Models\Staff;
use Illuminate\Support\Carbon;

class CreateNewStaff
{
    public function create(array $data)
    {
        // Проверка открытого и закрытого ключа на актуальность
        $now = Carbon::now();
        $validTo = Carbon::createFromTimestampMs($data['certificate']['valid_to']);
        $closeKeyValidTo = Carbon::createFromTimestampMs($data['certificate']['close_key_valid_to']);

        if ($validTo->isFuture()) {
            if ($now->diffInMonths($validTo) < 1) {
                $data['certificate']['is_request_new'] = true;
            } else {
                $data['certificate']['is_request_new'] = false;
            }
            $data['certificate']['is_valid'] = true;
        } else {
            $data['certificate']['is_valid'] = false;
        }

        if ($closeKeyValidTo->isFuture()) {
            $data['certificate']['close_key_is_valid'] = true;
        } else {
            $data['certificate']['close_key_is_valid'] = false;
        }

        $certification = $data['certificate'];

        $data['gender'] = 'slava';

        $staff = Staff::updateOrCreate(['inn' => $data['inn']], $data);
        if ($staff->certification()->exists()) $staff->certification->delete();
        $staff->certification()->create($certification);
        $staff->searchable();

        return $staff;
    }
}

<?php

namespace App\Actions\Eds;

use App\Models\Staff;
use Illuminate\Support\Carbon;

class CreateNewStaff
{
    public function create(array $data)
    {
        // Проверка открытого ключа на актуальность
        $now = Carbon::now();
        $validTo = Carbon::createFromTimestampMs($data['certificate']['valid_to']);

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

        $certification = $data['certificate'];

        $data['gender'] = 'slava';

        $staff = Staff::updateOrCreate(['snils' => $data['snils']], $data);
        $staff->certification()->updateOrCreate($certification);

        return $staff;
    }
}

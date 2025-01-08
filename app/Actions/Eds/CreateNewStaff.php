<?php

namespace App\Actions\Eds;

use App\Models\Staff;

class CreateNewStaff
{
    public function create(array $data)
    {
        $certification = $data['certificate'];

        $data['gender'] = 'slava';

        $staff = Staff::create($data);

        $staff->certification()->updateOrCreate($certification);

        return $staff;
    }
}

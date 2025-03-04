<?php

namespace App\Actions\Mis;

use App\Models\Staff;

class CheckCertificationIdentify
{
    public function __construct(Staff $staff)
    {
        $misUserId = $staff->mis_user_id;

    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MisLPUDoctorToUserID extends Model
{
    protected $table = 'mis_lpu_doctor_to_user_id';

    protected $fillable = [
        'lpu_doctor_id',
        'user_id',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MisPasswordHistory extends Model
{
    protected $fillable = [
        'user_id',
        'guid',
        'original_password',
        'password',
    ];
}

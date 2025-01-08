<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffIntegrate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'login',
        'password',
        'link',
        'staff_id',
        'deleted_at'
    ];

    public function staff(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Staff::class);
    }
}

<?php

namespace App\Models\Mis;

use Illuminate\Database\Eloquent\Relations\Pivot;

class XUserRole extends Pivot
{
    protected $connection = 'mis';
    protected $table = 'x_UserRole';
    protected $primaryKey = 'UserRoleID';
    public $timestamps = false;

    protected $fillable = [
        'UserID',
        'RoleID',
        'x_Edition',
        'x_Status',
    ];

    protected $attributes = [
        'x_Edition' => 0,
        'x_Status'  => 1,
    ];

    protected static function booted(): void
    {
        static::updating(function (XUserRole $model) {
            $model->x_Edition = ($model->getOriginal('x_Edition') ?? 0) + 1;
        });
    }
}

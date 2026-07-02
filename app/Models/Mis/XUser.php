<?php

namespace App\Models\Mis;

use Illuminate\Database\Eloquent\Model;

class XUser extends Model
{
    protected $connection = 'mis';
    protected $table = 'x_User';
    protected $primaryKey = 'UserID';
    public $timestamps = false;

    protected $fillable = [
        'WindowsName',
        'GeneralLogin',
        'GeneralPassword',
        'AuthMode',
        'FIO',
        'x_Edition',
        'x_Status',
        'GUID',
        'Email',
    ];

    protected $attributes = [
        'WindowsName'     => '',
        'GeneralPassword' => '',
        'AuthMode'        => 1,
        'FIO'             => '',
        'x_Edition'       => 0,
        'x_Status'        => 1,
        'Email'           => '',
    ];

    protected static function booted(): void
    {
        static::updating(function (XUser $model) {
            $model->x_Edition = ($model->getOriginal('x_Edition') ?? 0) + 1;
        });
    }

    public function settings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(XUserSettings::class, 'rf_UserID', 'UserID');
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(XRole::class, 'x_UserRole', 'UserID', 'RoleID')
            ->withPivot(['UserRoleID', 'x_Edition', 'x_Status'])
            ->using(XUserRole::class);
    }
}

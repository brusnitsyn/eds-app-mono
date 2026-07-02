<?php

namespace App\Models\Mis;

use Illuminate\Database\Eloquent\Model;

class XRole extends Model
{
    protected $connection = 'mis';
    protected $table = 'x_Role';
    protected $primaryKey = 'RoleID';
    public $timestamps = false;

    protected $fillable = [
        'Name',
        'ThemeID',
        'GUID',
        'Type',
        'x_Edition',
        'x_Status',
    ];

    protected $attributes = [
        'Type'      => 0,
        'x_Edition' => 0,
        'x_Status'  => 1,
    ];

    protected static function booted(): void
    {
        static::updating(function (XRole $model) {
            $model->x_Edition = ($model->getOriginal('x_Edition') ?? 0) + 1;
        });
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(XUser::class, 'x_UserRole', 'RoleID', 'UserID')
            ->withPivot(['UserRoleID', 'x_Edition', 'x_Status'])
            ->using(XUserRole::class);
    }
}

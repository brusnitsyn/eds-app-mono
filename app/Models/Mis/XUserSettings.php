<?php

namespace App\Models\Mis;

use Illuminate\Database\Eloquent\Model;

class XUserSettings extends Model
{
    protected $connection = 'mis';
    protected $table = 'x_UserSettings';
    protected $primaryKey = 'UserSettingID';
    public $timestamps = false;

    protected $fillable = [
        'rf_UserID',
        'OwnerGUID',
        'DocTypeDefGUID',
        'rf_SettingTypeID',
        'Property',
        'ValueInt',
        'ValueStr',
        'ValueText',
        'ValueDate',
        'x_Edition',
        'x_Status',
    ];

    protected $attributes = [
        'ValueInt'  => 0,
        'ValueStr'  => '',
        'ValueText' => '',
        'ValueImg'  => '',
        'ValueDate' => '1900-01-01 00:00:00',
        'x_Edition' => 0,
        'x_Status'  => 1,
    ];

    protected $casts = [
        'ValueDate' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (XUserSettings $model) {
            $model->x_Edition = ($model->getOriginal('x_Edition') ?? 0) + 1;
        });
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(XUser::class, 'rf_UserID', 'UserID');
    }
}

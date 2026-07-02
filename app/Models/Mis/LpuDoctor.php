<?php

namespace App\Models\Mis;

use Illuminate\Database\Eloquent\Model;

class LpuDoctor extends Model
{
    protected $connection = 'mis';
    protected $table = 'hlt_LPUDoctor';
    protected $primaryKey = 'LPUDoctorID';
    public $timestamps = false;

    protected $fillable = [
        'x_Edition',
        'x_Status',
        'PCOD',
        'OT_V',
        'IM_V',
        'D_SER',
        'rf_PRVSID',
        'FAM_V',
        'rf_KV_KATID',
        'MSG_Text',
        'rf_LPUID',
        'isDoctor',
        'rf_HealingRoomID',
        'inTime',
        'DR',
        'IsSpecial',
        'rf_PRVDID',
        'UGUID',
        'SS',
        'rf_DepartmentID',
        'DE_SER',
        'Phone',
        'DateBegin',
        'DateEnd',
        'IsDismissal',
        'rf_kl_SexID',
        'Email',
        'SeriesDoc',
        'NumberDoc',
        'DateDoc',
        'DocIssuedBy',
        'DateActualization',
        'rf_TypeDocID',
        'OID',
    ];

    protected $attributes = [
        'x_Edition'         => 0,
        'x_Status'          => 1,
        'OT_V'              => '',
        'IM_V'              => '',
        'FAM_V'             => '',
        'MSG_Text'          => '',
        'SS'                => '',
        'Phone'             => '',
        'Email'             => '',
        'SeriesDoc'         => '',
        'NumberDoc'         => '',
        'DocIssuedBy'       => '',
        'OID'               => '',
        'rf_KV_KATID'       => 0,
        'rf_HealingRoomID'  => 0,
        'inTime'            => 0,
        'isDoctor'          => 1,
        'IsSpecial'         => 0,
        'IsDismissal'       => 0,
        'rf_kl_SexID'       => 0,
        'rf_TypeDocID'      => 0,
    ];

    protected $casts = [
        'DR'               => 'datetime',
        'D_SER'            => 'datetime',
        'DE_SER'           => 'datetime',
        'DateBegin'        => 'datetime',
        'DateEnd'          => 'datetime',
        'DateDoc'          => 'datetime',
        'DateActualization'=> 'datetime',
        'isDoctor'         => 'boolean',
        'IsSpecial'        => 'boolean',
        'IsDismissal'      => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (LpuDoctor $model) {
            $model->x_Edition = ($model->getOriginal('x_Edition') ?? 0) + 1;
        });
    }

    public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DocPrvd::class, 'rf_LPUDoctorID', 'LPUDoctorID');
    }
}

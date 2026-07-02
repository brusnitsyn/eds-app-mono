<?php

namespace App\Models\Mis;

use Illuminate\Database\Eloquent\Model;

class DocPrvd extends Model
{
    protected $connection = 'mis';
    protected $table = 'hlt_DocPRVD';
    protected $primaryKey = 'DocPRVDID';
    public $timestamps = false;

    protected $fillable = [
        'x_Edition',
        'x_Status',
        'rf_LPUDoctorID',
        'D_PRIK',
        'S_ST',
        'D_END',
        'rf_PRVSID',
        'rf_HealingRoomID',
        'rf_KV_KATID',
        'rf_DepartmentID',
        'MainWorkPlace',
        'InTime',
        'GUID',
        'rf_PRVDID',
        'Name',
        'rf_EquipmentID',
        'rf_ResourceTypeID',
        'ShownInSchedule',
        'ERID',
        'ERName',
        'NomServiceCode',
        'isDismissal',
        'isSpecial',
        'rf_kl_DepartmentTypeID',
        'Interval',
        'isUseInterval',
        'PCOD',
        'IsConclusion',
        'rf_AssignmentDoctorID',
        'rf_kl_DepartmentProfileID',
        'rf_kl_MedCareTypeID',
        'rf_ResourceProfileID',
        'rf_ResourceTypeGroupID',
        'rf_kl_FrmrPrvdID',
        'rf_kl_FrmrPrvsID',
        'IsWaitingListAllow',
        'IsPostNurse',
        'IsMobileBrigade',
        'rf_kl_SubComissionTypeID',
        'DateActualization',
        'FrmrGuid',
    ];

    protected $attributes = [
        'x_Edition'                => 0,
        'x_Status'                 => 1,
        'Name'                     => '',
        'ERID'                     => '',
        'ERName'                   => '',
        'NomServiceCode'           => '',
        'PCOD'                     => '',
        'S_ST'                     => 1.000,
        'InTime'                   => 0,
        'Interval'                 => 0,
        'isUseInterval'            => 0,
        'rf_EquipmentID'           => 0,
        'rf_KV_KATID'              => 0,
        'rf_HealingRoomID'         => 0,
        'rf_AssignmentDoctorID'    => 0,
        'rf_kl_DepartmentProfileID'=> 0,
        'rf_kl_DepartmentTypeID'   => 0,
        'rf_kl_MedCareTypeID'      => 0,
        'rf_ResourceProfileID'     => 0,
        'rf_ResourceTypeGroupID'   => 0,
        'rf_kl_FrmrPrvdID'        => 0,
        'rf_kl_FrmrPrvsID'        => 0,
        'rf_kl_SubComissionTypeID' => 0,
        'MainWorkPlace'            => 1,
        'ShownInSchedule'          => 0,
        'isDismissal'              => 0,
        'isSpecial'                => 0,
        'IsConclusion'             => 0,
        'IsWaitingListAllow'       => 0,
        'IsPostNurse'              => 0,
        'IsMobileBrigade'          => 0,
    ];

    protected $casts = [
        'D_PRIK'            => 'datetime',
        'D_END'             => 'datetime',
        'DateActualization' => 'datetime',
        'S_ST'              => 'decimal:3',
        'MainWorkPlace'     => 'boolean',
        'ShownInSchedule'   => 'boolean',
        'isDismissal'       => 'boolean',
        'isSpecial'         => 'boolean',
        'IsConclusion'      => 'boolean',
        'IsWaitingListAllow'=> 'boolean',
        'IsPostNurse'       => 'boolean',
        'IsMobileBrigade'   => 'boolean',
        'isUseInterval'     => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (DocPrvd $model) {
            $model->x_Edition = ($model->getOriginal('x_Edition') ?? 0) + 1;
        });
    }

    public function doctor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LpuDoctor::class, 'rf_LPUDoctorID', 'LPUDoctorID');
    }
}

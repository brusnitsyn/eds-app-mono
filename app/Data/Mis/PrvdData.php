<?php

namespace App\Data\Mis;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class PrvdData extends Data
{
    #[MapInputName('D_PRIK')]
    #[MapOutputName('D_PRIK')]
    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
    #[Date]
    public CarbonImmutable|Optional $start_at;
    #[MapInputName('D_END')]
    #[MapOutputName('D_END')]
    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
    #[Date]
    public CarbonImmutable|Optional $end_at;
    public function __construct(
        #[MapInputName('DocPRVDID')]
        #[MapOutputName('DocPRVDID')]
        public int|Optional $id,
        #[MapInputName('rf_LPUDoctorID')]
        #[MapOutputName('rf_LPUDoctorID')]
        public int|Optional $doctor_id,
        #[MapInputName('S_ST')]
        #[MapOutputName('S_ST')]
        public float $rate,
        #[MapInputName('rf_PRVSID')]
        #[MapOutputName('rf_PRVSID')]
        public int $prvs_id,
        #[MapInputName('rf_HealingRoomID')]
        #[MapOutputName('rf_HealingRoomID')]
        public int|Optional $healing_room_id,
        #[MapInputName('rf_DepartmentID')]
        #[MapOutputName('rf_DepartmentID')]
        public int $department_id,
        #[MapInputName('rf_kl_DepartmentProfileID')]
        #[MapOutputName('rf_kl_DepartmentProfileID')]
        public Optional|int $department_profile_id,
        #[MapInputName('rf_kl_DepartmentTypeID')]
        #[MapOutputName('rf_kl_DepartmentTypeID')]
        public Optional|int $department_type_id,
        #[MapInputName('MainWorkPlace')]
        #[MapOutputName('MainWorkPlace')]
        public bool $main_work_place,
        #[MapInputName('InTime')]
        #[MapOutputName('InTime')]
        public bool $in_time,
        #[MapInputName('GUID')]
        #[MapOutputName('GUID')]
        public string $guid,
        #[MapInputName('rf_PRVDID')]
        #[MapOutputName('rf_PRVDID')]
        public int $prvd_id,
        #[MapInputName('Name')]
        #[MapOutputName('Name')]
        public string|null $name,
        #[MapInputName('ShownInSchedule')]
        #[MapOutputName('ShownInSchedule')]
        public bool $shown_in_schedule,
        #[MapInputName('isDismissal')]
        #[MapOutputName('isDismissal')]
        public bool $is_dismissal,
        #[MapInputName('isSpecial')]
        #[MapOutputName('isSpecial')]
        public bool $is_special,
        #[MapInputName('PCOD')]
        #[MapOutputName('PCOD')]
        public string $code,
        #[MapInputName('rf_kl_FrmrPrvsID')]
        #[MapOutputName('rf_kl_FrmrPrvsID')]
        public int|Optional $frmr_prvd_id,
        #[MapInputName('rf_ResourceTypeID')]
        #[MapOutputName('rf_ResourceTypeID')]
        public int|Optional $resource_type_id = 1,
    ) {
        $this->start_at = CarbonImmutable::now();
        $this->end_at = CarbonImmutable::create(2222, 01, 01);
    }

    public function toOriginal(): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'rate' => $this->rate,
            'prvs_id' => $this->prvs_id,
            'healing_room_id' => $this->healing_room_id,
            'department_id' => $this->department_id,
            'department_profile_id' => $this->department_profile_id,
            'department_type_id' => $this->department_type_id,
            'main_work_place' => $this->main_work_place,
            'in_time' => $this->in_time,
            'guid' => $this->guid,
            'prvd_id' => $this->prvd_id,
            'name' => $this->name,
            'shown_in_schedule' => $this->shown_in_schedule,
            'is_dismissal' => $this->is_dismissal,
            'is_special' => $this->is_special,
            'code' => $this->code,
            'frmr_prvd_id' => $this->frmr_prvd_id,
            'start_at' => $this->start_at->format('Y-m-d H:i:s.u'),
            'end_at' => $this->end_at->format('Y-m-d H:i:s.u')
        ];
    }
}

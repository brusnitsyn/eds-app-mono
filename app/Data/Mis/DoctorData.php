<?php

namespace App\Data\Mis;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class DoctorData extends Data
{
    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
    #[MapInputName('DateEnd')]
    #[MapOutputName('DateEnd')]
    #[Date]
    public CarbonImmutable|Optional $end_at;

    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
    #[MapInputName('DateBegin')]
    #[MapOutputName('DateBegin')]
    #[Date]
    public CarbonImmutable|Optional $start_at;

    public function __construct(
        #[MapInputName('LPUDoctorID')]
        #[MapOutputName('LPUDoctorID')]
        public Optional|null|int $id,
        #[MapInputName('PCOD')]
        #[MapOutputName('PCOD')]
        public string $code,
        #[MapInputName('OT_V')]
        #[MapOutputName('OT_V')]
        public string $middle_name,
        #[MapInputName('IM_V')]
        #[MapOutputName('IM_V')]
        public string $first_name,
        #[MapInputName('FAM_V')]
        #[MapOutputName('FAM_V')]
        public string $last_name,
        #[MapInputName('DR')]
        #[MapOutputName('DR')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public CarbonImmutable $brith_at,
        #[MapInputName('SS')]
        #[MapOutputName('SS')]
        public string $snils,
        #[MapInputName('isDoctor')]
        #[MapOutputName('isDoctor')]
        public bool $is_doctor,
        #[MapInputName('inTime')]
        #[MapOutputName('inTime')]
        public bool $in_time,
        #[MapInputName('isSpecial')]
        #[MapOutputName('isSpecial')]
        public bool $is_special,
        #[MapInputName('isDismissal')]
        #[MapOutputName('isDismissal')]
        public bool $is_dismissal,
        #[MapInputName('C_PRVS')]
        #[MapOutputName('C_PRVS')]
        public Optional|null|string $prvs_code,
        #[MapInputName('PRVS_NAME')]
        #[MapOutputName('PRVS_NAME')]
        public Optional|null|string $prvs_name,
        #[MapInputName('M_NAMES')]
        #[MapOutputName('M_NAMES')]
        public Optional|null|string $lpu_name,
        #[MapInputName('DepartmentName')]
        #[MapOutputName('DepartmentName')]
        public Optional|null|string $department_name,
        #[MapInputName('NAME')]
        #[MapOutputName('NAME')]
        public Optional|null|string $prvd_name,
        #[MapInputName('rf_PRVSID')]
        #[MapOutputName('rf_PRVSID')]
        public int $prvs_id,
        #[MapInputName('rf_LPUID')]
        #[MapOutputName('rf_LPUID')]
        public int $lpu_id,
        #[MapInputName('rf_PRVDID')]
        #[MapOutputName('rf_PRVDID')]
        public int $prvd_id,
        #[MapInputName('rf_DepartmentID')]
        #[MapOutputName('rf_DepartmentID')]
        public int $department_id,
        #[MapInputName('UGUID')]
        #[MapOutputName('UGUID')]
        public Optional|string $guid,
        public bool $has_password_change = false,
    ) {
        $this->end_at = CarbonImmutable::parse('2222-01-01T00:00:00.000');
        $this->start_at = CarbonImmutable::now();
    }

    public function toOriginal(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'middle_name' => $this->middle_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'brith_at' => $this->brith_at->format('Y-m-d H:i:s.u'),
            'snils' => $this->snils,
            'is_doctor' => $this->is_doctor,
            'in_time' => $this->in_time,
            'is_special' => $this->is_special,
            'is_dismissal' => $this->is_dismissal,
            'prvs_code' => $this->prvs_code,
            'prvs_name' => $this->prvs_name,
            'start_at' => $this->start_at->format('Y-m-d H:i:s.u'),
            'end_at' => $this->end_at->format('Y-m-d H:i:s.u'),
            'lpu_name' => $this->lpu_name,
            'department_name' => $this->department_name,
            'prvd_name' => $this->prvd_name,
            'prvs_id' => $this->prvs_id,
            'lpu_id' => $this->lpu_id,
            'prvd_id' => $this->prvd_id,
            'department_id' => $this->department_id,
            'guid' => $this->guid,
            'has_password_change' => $this->has_password_change,
        ];
    }

    public function toMis(): array
    {
        return [

        ];
    }
}

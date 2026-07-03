<?php

namespace App\Data\Mis;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class DoctorData extends Data
{
    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
    #[MapOutputName('DateEnd')]
    #[Date]
    public CarbonImmutable|Optional $end_at;

    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
    #[MapOutputName('DateBegin')]
    #[Date]
    public CarbonImmutable|Optional $start_at;

    public function __construct(
        #[MapOutputName('LPUDoctorID')]
        public Optional|null|int $id,
        #[MapOutputName('PCOD')]
        public string $code,
        #[MapOutputName('OT_V')]
        public string $middle_name,
        #[MapOutputName('IM_V')]
        public string $first_name,
        #[MapOutputName('FAM_V')]
        public string $last_name,
        #[MapOutputName('DR')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public CarbonImmutable $brith_at,
        #[MapOutputName('SS')]
        public string $snils,
        #[MapOutputName('isDoctor')]
        public bool $is_doctor,
        #[MapOutputName('inTime')]
        public bool $in_time,
        #[MapOutputName('isSpecial')]
        public bool $is_special,
        #[MapOutputName('isDismissal')]
        public bool $is_dismissal,
        #[MapOutputName('C_PRVS')]
        public Optional|null|string $prvs_code,
        #[MapOutputName('PRVS_NAME')]
        public Optional|null|string $prvs_name,
        #[MapOutputName('M_NAMES')]
        public Optional|null|string $lpu_name,
        #[MapOutputName('DepartmentName')]
        public Optional|null|string $department_name,
        #[MapOutputName('NAME')]
        public Optional|null|string $prvd_name,
        #[MapOutputName('rf_PRVSID')]
        public int $prvs_id,
        #[MapOutputName('rf_LPUID')]
        public int $lpu_id,
        #[MapOutputName('rf_PRVDID')]
        public int $prvd_id,
        #[MapOutputName('rf_DepartmentID')]
        public int $department_id,
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
            'id'             => $this->id,
            'code'           => $this->code,
            'middle_name'    => $this->middle_name,
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'brith_at'       => $this->brith_at->format('Y-m-d H:i:s.u'),
            'snils'          => $this->snils,
            'is_doctor'      => $this->is_doctor,
            'in_time'        => $this->in_time,
            'is_special'     => $this->is_special,
            'is_dismissal'   => $this->is_dismissal,
            'prvs_code'      => $this->prvs_code,
            'prvs_name'      => $this->prvs_name,
            'start_at'       => $this->start_at->format('Y-m-d H:i:s.u'),
            'end_at'         => $this->end_at->format('Y-m-d H:i:s.u'),
            'lpu_name'       => $this->lpu_name,
            'department_name'=> $this->department_name,
            'prvd_name'      => $this->prvd_name,
            'prvs_id'        => $this->prvs_id,
            'lpu_id'         => $this->lpu_id,
            'prvd_id'        => $this->prvd_id,
            'department_id'  => $this->department_id,
            'guid'           => $this->guid,
            'has_password_change' => $this->has_password_change,
        ];
    }
}

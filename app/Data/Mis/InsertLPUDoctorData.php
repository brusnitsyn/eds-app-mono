<?php

namespace App\Data\Mis;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class InsertLPUDoctorData extends Data
{
    // 'PCOD' => ['required', 'string'],
    // 'OT_V' => ['required', 'string'],
    // 'IM_V' => ['required', 'string'],
    // 'FAM_V' => ['required', 'string'],
    // 'DR' => ['required', 'string'],
    // 'SS' => ['required', 'string'],
    // 'isDoctor' => ['required', 'boolean'],
    // 'inTime' => ['required', 'boolean'],
    // 'isSpecial' => ['required', 'boolean'],
    // 'isDismissal' => ['required', 'boolean'],
    // 'S_ST' => ['required', 'numeric'],
    // 'rf_LPUID' => ['required', 'numeric'],
    // 'rf_PRVSID' => ['required', 'numeric'],
    // 'rf_DepartmentID' => ['required', 'numeric'],
    // 'rf_PRVDID' => ['required', 'numeric'],
    public function __construct(
        #[MapInputName('code')]
        #[MapOutputName('PCOD')]
        public string $code,
        #[MapInputName('middle_name')]
        #[MapOutputName('OT_V')]
        public string $middle_name,
        #[MapInputName('first_name')]
        #[MapOutputName('IM_V')]
        public string $first_name,
        #[MapInputName('last_name')]
        #[MapOutputName('FAM_V')]
        public string $last_name,
        #[MapInputName('birth_at')]
        #[MapOutputName('DR')]
        public \DateTime $birth_at,
        #[MapInputName('snils')]
        #[MapOutputName('SS')]
        public string $snils,
        #[MapInputName('has_doctor')]
        #[MapOutputName('isDoctor')]
        public bool $has_doctor,
        #[MapInputName('has_time')]
        #[MapOutputName('inTime')]
        public bool $has_time,
        #[MapInputName('has_special')]
        #[MapOutputName('IsSpecial')]
        public bool $has_special,
        #[MapInputName('has_dismissal')]
        #[MapOutputName('IsDismissal')]
        public bool $has_dismissal,
        #[MapInputName('rate')]
        #[MapOutputName('S_ST')]
        public float $rate,
        #[MapInputName('lpu_id')]
        #[MapOutputName('rf_LPUID')]
        public int $lpu_id,
        #[MapInputName('prvs_id')]
        #[MapOutputName('rf_PRVSID')]
        public int $prvs_id,
        #[MapInputName('department_id')]
        #[MapOutputName('rf_DepartmentID')]
        public int $department_id,
        #[MapInputName('prvd_id')]
        #[MapOutputName('rf_PRVDID')]
        public int $prvd_id,
        #[MapInputName('guid')]
        #[MapOutputName('UGUID')]
        public string $guid,
    ) {}
}

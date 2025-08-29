<?php

namespace App\Data\Mis;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class InsertPRVDDoctorData extends Data
{
    #[MapInputName('in_special')]
    #[MapOutputName('isSpecial')]
    public bool $in_special = false;

    #[MapInputName('resource_type_id')]
    #[MapOutputName('rf_ResourceTypeID')]
    public int $resource_type_id = 1;

    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
    #[MapInputName('end_at')]
    #[MapOutputName('D_END')]
    #[Date]
    public CarbonImmutable $end_at;

    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
    #[MapInputName('start_at')]
    #[MapOutputName('D_PRIK')]
    #[Date]
    public CarbonImmutable $start_at;

    public function __construct(
        #[MapInputName('id')]
        #[MapOutputName('DocPRVDID')]
        public Optional|int $id,
        #[MapInputName('doctor_id')]
        #[MapOutputName('rf_LPUDoctorID')]
        public int $doctor_id,
        #[MapInputName('code')]
        #[MapOutputName('PCOD')]
        public string $code,
        #[MapInputName('rate')]
        #[MapOutputName('S_ST')]
        public float $rate,
        #[MapInputName('in_time')]
        #[MapOutputName('InTime')]
        public bool $in_time,
        #[MapInputName('is_dismissal')]
        #[MapOutputName('isDismissal')]
        public bool $is_dismissal,
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
        #[MapOutputName('GUID')]
        public string $guid,

    ) {
        $this->end_at = CarbonImmutable::parse('2222-01-01T00:00:00.000');
    }
}

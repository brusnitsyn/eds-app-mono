<?php

namespace App\Data\Mis\Import;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class DoctorImportData extends Data
{
    public function __construct(
        #[MapInputName('familiia')]
        #[MapOutputName('FAM_V')]
        public string $last_name,
        #[MapInputName('imia')]
        #[MapOutputName('IM_V')]
        public string $first_name,
        #[MapInputName('otcestvo')]
        #[MapOutputName('OT_V')]
        public string $middle_name,
        #[MapInputName('snils')]
        #[MapOutputName('SS')]
        public string $snils,
        #[MapOutputName('DR')]
        #[MapInputName('data_rozdeniia'), WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public \DateTime $brith_at,
        #[MapInputName('lpu')]
        #[MapOutputName('LPU_CODE')]
        public int $lpu_code,
        #[MapInputName('otdelenie')]
        #[MapOutputName('DEPARTMENT_CODE')]
        public int $department_code,
        #[MapInputName('dolznost')]
        #[MapOutputName('PRVD_CODE')]
        public int $prvd,
        #[MapInputName('specialnost')]
        #[MapOutputName('PRVS_CODE')]
        public int $prvs,
        #[MapOutputName('TEMPLATE_ROLE_ID')]
        #[MapInputName('sablon_roli')]
        public int $template_role_id,
    ) {}
}

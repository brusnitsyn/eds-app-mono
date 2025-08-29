<?php

namespace App\Data\Mis\Classifier;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class ClassifierLpuData extends Data
{
    // 'Oms_LPU.LPUID', 'Oms_LPU.MCOD', 'Oms_LPU.M_NAMES', 'Oms_LPU.DATE_E', 'Oms_LPU.GUIDLPU'
    public function __construct(
        #[MapInputName('LPUID')]
        public int $id,
        #[MapInputName('MCOD')]
        public string $code,
        #[MapInputName('M_NAMES')]
        public string $name,
        #[MapInputName('DATE_E')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public Carbon $ent_at,
        #[MapInputName('GUIDLPU')]
        public string $guid
    ) {}
}

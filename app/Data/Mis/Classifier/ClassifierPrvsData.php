<?php

namespace App\Data\Mis\Classifier;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class ClassifierPrvsData extends Data
{
    // 'Oms_PRVS.PRVSID', 'Oms_PRVS.C_PRVS', 'Oms_PRVS.PRVS_NAME', 'Oms_PRVS.Date_End', 'Oms_PRVS.PRVSGuid'
    public function __construct(
        #[MapInputName('PRVSID')]
        public int $id,
        #[MapInputName('C_PRVS')]
        public string $code,
        #[MapInputName('PRVS_NAME')]
        public string $name,
        #[MapInputName('Date_End')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public Carbon $ent_at,
        #[MapInputName('PRVSGuid')]
        public string $guid
    ) {}
}

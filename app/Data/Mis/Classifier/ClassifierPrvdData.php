<?php

namespace App\Data\Mis\Classifier;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class ClassifierPrvdData extends Data
{
    // 'Oms_PRVD.PRVDID', 'Oms_PRVD.C_PRVD', 'Oms_PRVD.NAME', 'Oms_PRVD.Date_E', 'Oms_PRVD.PRVDGuid'
    public function __construct(
        #[MapInputName('PRVDID')]
        public int $id,
        #[MapInputName('C_PRVD')]
        public string $code,
        #[MapInputName('NAME')]
        public string $name,
        #[MapInputName('Date_E')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public Carbon $ent_at,
        #[MapInputName('PRVDGuid')]
        public string $guid
    ) {}
}

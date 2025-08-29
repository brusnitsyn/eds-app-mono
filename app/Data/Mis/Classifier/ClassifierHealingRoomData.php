<?php

namespace App\Data\Mis\Classifier;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class ClassifierHealingRoomData extends Data
{
    public function __construct(
        #[MapInputName('HealingRoomID')]
        public int $id,
        #[MapInputName('DateBegin'), WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public \DateTime $start_at,
        #[MapInputName('Num')]
        public string $rate,
        #[MapInputName('DateEnd'), WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public \DateTime $end_at,
        #[MapInputName('Comment')]
        public string $comment,
        #[MapInputName('UGUID')]
        public string $guid,
        #[MapInputName('inTime')]
        public bool $in_time,
        #[MapInputName('rf_DepatementID')]
        public int $department_id,
    ) {}
}

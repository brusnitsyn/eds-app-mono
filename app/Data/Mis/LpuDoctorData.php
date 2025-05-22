<?php

namespace App\Data\Mis;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class LpuDoctorData extends Data
{
    public function __construct(
        public int $LPUDoctorID,
        public string $PCOD,
        public string $OT_V,
        public string $IM_V,
        public string $FAM_V,
        public string $D_SER,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public Carbon $DR,
        public string $SS,
        public bool $isDoctor,
        public bool $inTime,
        public bool $isSpecial,
        public bool $isDismissal,
        public string $C_PRVS,
        public string $PRVS_NAME,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public Carbon $Date_Beg,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public Carbon $Date_End,
        public string $M_NAMES,
        public string $DepartmentName,
        public string $NAME,
        public int $rf_PRVSID,
        public int $rf_LPUID,
        public int $rf_PRVDID,
        public int $rf_DepartmentID,
        public bool $has_password_change = false,
    ) {}

    public function toMisData() : array
    {

    }
}

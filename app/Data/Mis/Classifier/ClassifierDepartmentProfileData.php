<?php

namespace App\Data\Mis\Classifier;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class ClassifierDepartmentProfileData extends Data
{
    // 'oms_kl_DepartmentProfile.kl_DepartmentProfileID', 'oms_kl_DepartmentProfile.Code', 'oms_kl_DepartmentProfile.Name',
    // 'oms_kl_DepartmentProfile.Date_E'
    public function __construct(
        #[MapInputName('kl_DepartmentProfileID')]
        public int $id,
        #[MapInputName('Code')]
        public string $code,
        #[MapInputName('Name')]
        public string $name,
        #[MapInputName('Date_E')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public Carbon $ent_at,
    ) {}
}

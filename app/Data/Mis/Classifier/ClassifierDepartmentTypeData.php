<?php

namespace App\Data\Mis\Classifier;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class ClassifierDepartmentTypeData extends Data
{
    // 'oms_kl_DepartmentType.kl_DepartmentTypeID', 'oms_kl_DepartmentType.Code', 'oms_kl_DepartmentType.Name',
    // 'oms_kl_DepartmentType.Date_E', 'oms_kl_DepartmentType.DepartmentTypeGuid'
    public function __construct(
        #[MapInputName('kl_DepartmentTypeID')]
        public int $id,
        #[MapInputName('Code')]
        public string $code,
        #[MapInputName('Name')]
        public string $name,
        #[MapInputName('Date_E')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public Carbon $ent_at,
        #[MapInputName('DepartmentTypeGuid')]
        public string $guid
    ) {}
}

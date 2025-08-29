<?php

namespace App\Data\Mis\Classifier;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class ClassifierDepartmentData extends Data
{
    // 'oms_Department.DepartmentID', 'oms_Department.DepartmentCODE', 'oms_Department.DepartmentNAME',
    // 'oms_Department.rf_kl_DepartmentTypeID', 'oms_Department.rf_kl_DepartmentProfileID',
    // 'oms_Department.Date_E', 'oms_Department.GUIDDepartment'
    public function __construct(
        #[MapInputName('DepartmentID')]
        public int $id,
        #[MapInputName('DepartmentCODE')]
        public string $code,
        #[MapInputName('DepartmentNAME')]
        public string $name,
        #[MapInputName('rf_kl_DepartmentTypeID')]
        public int $type_id,
        #[MapInputName('rf_kl_DepartmentProfileID')]
        public int $profile_id,
        #[MapInputName('Date_E'), WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s.u')]
        public \DateTime $ent_at,
        #[MapInputName('GUIDDepartment')]
        public string $guid
    ) {}
}

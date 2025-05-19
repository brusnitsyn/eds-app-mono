<?php

namespace App\Data\Mis;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class XUserRole extends Data
{
    public function __construct(
        #[MapInputName('UserRoleID')]
        public int $user_role_id,
        #[MapInputName('UserID')]
        public int $user_id,
        #[MapInputName('RoleID')]
        public int $role_id
    ) {}
}

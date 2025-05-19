<?php

namespace App\Data\Mis;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class XRole extends Data
{
    public function __construct(
        #[MapInputName('RoleID')]
        public int $role_id,
        #[MapInputName('Name')]
        public string $name,
        #[MapInputName('GUID')]
        public string $guid,
    ) {}
}

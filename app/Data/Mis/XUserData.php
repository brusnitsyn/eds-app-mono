<?php

namespace App\Data\Mis;

use Spatie\LaravelData\Data;

class XUserData extends Data
{
    public function __construct(
        public string $id,
    ) {}
}

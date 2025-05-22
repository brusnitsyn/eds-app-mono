<?php

namespace App\Data\Mis;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class XUserMis extends Data
{
    public function __construct(
        #[MapInputName('UserID')]
        public int $UserID,
        #[MapInputName('GeneralLogin')]
        public string $GeneralLogin,
        #[MapInputName('GeneralPassword')]
        public string $GeneralPassword,
        #[MapInputName('GUID')]
        public string $GUID
    ) {}
}

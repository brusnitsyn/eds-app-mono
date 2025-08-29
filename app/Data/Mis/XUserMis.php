<?php

namespace App\Data\Mis;

use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class XUserMis extends Data
{
    public function __construct(
        #[MapInputName('UserID')]
        public int|null $UserID,
        #[MapInputName('GeneralLogin')]
        public string $GeneralLogin,
        #[MapInputName('GeneralPassword')]
        public string|null $GeneralPassword,
        #[MapInputName('FIO')]
        public string|null $FIO,
        #[MapInputName('GUID')]
        public string|null $GUID,
        #[MapInputName('AuthMode')]
        public int $AuthMode = 1
    ) {}
}

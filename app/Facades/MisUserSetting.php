<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static foo(string $args)
 *
 * @see \App\Services\MisUserSettingService
 */
class MisUserSetting extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'misusersetting.facade';
    }
}

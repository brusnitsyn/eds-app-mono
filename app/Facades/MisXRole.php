<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static foo(string $args)
 *
 * @see \App\Services\MisXRoleService
 */
class MisXRole extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'misxrole.facade';
    }
}

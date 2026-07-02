<?php

namespace App\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection getRoles()
 * @method static Collection getRolesByUserId(int $userId)
 * @method static void syncRoles(int $userId, array $roleIds)
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

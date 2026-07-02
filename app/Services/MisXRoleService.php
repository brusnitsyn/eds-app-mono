<?php

namespace App\Services;

use App\Models\Mis\XRole;
use App\Models\Mis\XUser;
use App\Models\Mis\XUserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MisXRoleService
{
    public function getRoles(): Collection
    {
        return Cache::flexible('mis_roles', [604800, 864000], function () {
            return XRole::select(['RoleID', 'Name', 'GUID'])
                ->where('RoleID', '>', 0)
                ->orderBy('Name')
                ->get()
                ->map(fn($r) => ['RoleID' => $r->RoleID, 'Name' => $r->Name, 'GUID' => $r->GUID]);
        });
    }

    public function getRolesByUserId(int $userId): Collection
    {
        return XUserRole::select(['UserRoleID', 'UserID', 'RoleID'])
            ->where('UserID', $userId)
            ->where('UserRoleID', '>', 0)
            ->orderBy('RoleID')
            ->get()
            ->map(fn($r) => ['user_role_id' => $r->UserRoleID, 'user_id' => $r->UserID, 'role_id' => $r->RoleID]);
    }

    public function syncRoles(int $userId, array $roleIds): void
    {
        XUser::findOrFail($userId)->roles()->sync($roleIds);
    }
}

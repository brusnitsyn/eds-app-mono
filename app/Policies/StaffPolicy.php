<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Контроль доступа к ПДн сотрудников (УПД.2, УПД.4, УПД.5).
 */
class StaffPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasScope(config('permissions.CAN_READ_STAFF'));
    }

    public function view(User $user, Staff $staff): bool
    {
        return $user->hasScope(config('permissions.CAN_READ_STAFF'));
    }

    public function create(User $user): bool
    {
        return $user->hasScope(config('permissions.CAN_CREATE_STAFF'));
    }

    public function update(User $user, Staff $staff): bool
    {
        return $user->hasScope(config('permissions.CAN_UPDATE_STAFF'));
    }

    public function delete(User $user, Staff $staff): bool
    {
        return $user->hasScope(config('permissions.CAN_DELETE_STAFF'));
    }

    public function downloadCertificates(User $user): bool
    {
        return $user->hasScope(config('permissions.CAN_DOWNLOAD_CERTIFICATION'));
    }

    public function installCertificates(User $user): bool
    {
        return $user->hasScope(config('permissions.CAN_INSTALL_CERTIFICATION_ON_MIS'));
    }
}

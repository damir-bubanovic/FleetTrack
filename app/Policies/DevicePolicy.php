<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('devices.view');
    }

    public function view(User $user, Device $device): bool
    {
        return $user->can('devices.view')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $device->company_id
            );
    }

    public function create(User $user): bool
    {
        return $user->can('devices.create')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id !== null
            );
    }

    public function update(User $user, Device $device): bool
    {
        return $user->can('devices.update')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $device->company_id
            );
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->can('devices.delete')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $device->company_id
            );
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
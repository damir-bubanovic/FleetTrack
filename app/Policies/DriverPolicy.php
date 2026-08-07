<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Driver;
use App\Models\User;

class DriverPolicy
{
    /**
     * Determine whether the user can view any drivers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('drivers.view');
    }

    /**
     * Determine whether the user can view the driver.
     */
    public function view(User $user, Driver $driver): bool
    {
        return $user->can('drivers.view')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $driver->company_id
            );
    }

    /**
     * Determine whether the user can create drivers.
     */
    public function create(User $user): bool
    {
        return $user->can('drivers.create')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id !== null
            );
    }

    /**
     * Determine whether the user can update the driver.
     */
    public function update(User $user, Driver $driver): bool
    {
        return $user->can('drivers.update')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $driver->company_id
            );
    }

    /**
     * Determine whether the user can delete the driver.
     */
    public function delete(User $user, Driver $driver): bool
    {
        return $user->can('drivers.delete')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $driver->company_id
            );
    }

    /**
     * Determine whether the user can restore the driver.
     */
    public function restore(User $user, Driver $driver): bool
    {
        return $user->can('drivers.update')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $driver->company_id
            );
    }

    /**
     * Determine whether the user can permanently delete the driver.
     */
    public function forceDelete(User $user, Driver $driver): bool
    {
        return $this->isSuperAdmin($user)
            && $user->can('drivers.delete');
    }

    /**
     * Determine whether the user is a global Super Administrator.
     */
    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}

<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    /**
     * Determine whether the user can view any vehicles.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vehicles.view');
    }

    /**
     * Determine whether the user can view the vehicle.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.view')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $vehicle->company_id
            );
    }

    /**
     * Determine whether the user can create vehicles.
     */
    public function create(User $user): bool
    {
        return $user->can('vehicles.create')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id !== null
            );
    }

    /**
     * Determine whether the user can update the vehicle.
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.update')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $vehicle->company_id
            );
    }

    /**
     * Determine whether the user can delete the vehicle.
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.delete')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $vehicle->company_id
            );
    }

    /**
     * Determine whether the user can restore the vehicle.
     */
    public function restore(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.update')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $vehicle->company_id
            );
    }

    /**
     * Determine whether the user can permanently delete the vehicle.
     */
    public function forceDelete(User $user, Vehicle $vehicle): bool
    {
        return $this->isSuperAdmin($user)
            && $user->can('vehicles.delete');
    }

    /**
     * Determine whether the user is a global Super Administrator.
     */
    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}

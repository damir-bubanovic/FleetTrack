<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Fleet;
use App\Models\User;

class FleetPolicy
{
    /**
     * Determine whether the user can view any fleets.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('fleets.view');
    }

    /**
     * Determine whether the user can view the fleet.
     */
    public function view(User $user, Fleet $fleet): bool
    {
        return $user->can('fleets.view')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $fleet->company_id
            );
    }

    /**
     * Determine whether the user can create fleets.
     */
    public function create(User $user): bool
    {
        return $user->can('fleets.create')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id !== null
            );
    }

    /**
     * Determine whether the user can update the fleet.
     */
    public function update(User $user, Fleet $fleet): bool
    {
        return $user->can('fleets.update')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $fleet->company_id
            );
    }

    /**
     * Determine whether the user can delete the fleet.
     */
    public function delete(User $user, Fleet $fleet): bool
    {
        return $user->can('fleets.delete')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $fleet->company_id
            );
    }

    /**
     * Determine whether the user can restore the fleet.
     */
    public function restore(User $user, Fleet $fleet): bool
    {
        return $user->can('fleets.update')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $fleet->company_id
            );
    }

    /**
     * Determine whether the user can permanently delete the fleet.
     */
    public function forceDelete(User $user, Fleet $fleet): bool
    {
        return $this->isSuperAdmin($user)
            && $user->can('fleets.delete');
    }

    /**
     * Determine whether the user is a global Super Administrator.
     */
    private function isSuperAdmin(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin
            && $user->company_id === null;
    }
}
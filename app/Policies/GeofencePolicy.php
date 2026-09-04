<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Geofence;
use App\Models\User;

class GeofencePolicy
{
    /**
     * Determine whether the user can view any geofences.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('geofences.view');
    }

    /**
     * Determine whether the user can view the geofence.
     */
    public function view(User $user, Geofence $geofence): bool
    {
        return $user->can('geofences.view')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $geofence->company_id
            );
    }

    /**
     * Determine whether the user can create geofences.
     */
    public function create(User $user): bool
    {
        return $user->can('geofences.create')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id !== null
            );
    }

    /**
     * Determine whether the user can update the geofence.
     */
    public function update(User $user, Geofence $geofence): bool
    {
        return $user->can('geofences.update')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $geofence->company_id
            );
    }

    /**
     * Determine whether the user can delete the geofence.
     */
    public function delete(User $user, Geofence $geofence): bool
    {
        return $user->can('geofences.delete')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $geofence->company_id
            );
    }

    /**
     * Determine whether the user can restore the geofence.
     */
    public function restore(User $user, Geofence $geofence): bool
    {
        return $user->can('geofences.update')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $geofence->company_id
            );
    }

    /**
     * Determine whether the user can permanently delete the geofence.
     */
    public function forceDelete(User $user, Geofence $geofence): bool
    {
        return $this->isSuperAdmin($user)
            && $user->can('geofences.delete');
    }

    /**
     * Determine whether the user is a global Super Administrator.
     */
    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}

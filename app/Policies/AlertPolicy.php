<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\User;

final class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('alerts.view');
    }

    public function view(User $user, Alert $alert): bool
    {
        if (! $user->can('alerts.view')) {
            return false;
        }

        return $this->isSuperAdmin($user)
            || $user->company_id === $alert->company_id;
    }

    public function acknowledge(User $user, Alert $alert): bool
    {
        if (! $user->can('alerts.acknowledge')) {
            return false;
        }

        return $this->isSuperAdmin($user)
            || $user->company_id === $alert->company_id;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value)
            && $user->company_id === null;
    }
}

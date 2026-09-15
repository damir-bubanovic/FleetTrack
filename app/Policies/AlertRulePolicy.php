<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AlertRule;
use App\Models\User;

final class AlertRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('alert-rules.view');
    }

    public function view(User $user, AlertRule $alertRule): bool
    {
        return $user->can('alert-rules.view')
            && $this->belongsToUsersCompany($user, $alertRule);
    }

    public function create(User $user): bool
    {
        return $user->can('alert-rules.create')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id !== null
            );
    }

    public function update(User $user, AlertRule $alertRule): bool
    {
        return $user->can('alert-rules.update')
            && $this->belongsToUsersCompany($user, $alertRule);
    }

    public function delete(User $user, AlertRule $alertRule): bool
    {
        return $user->can('alert-rules.delete')
            && $this->belongsToUsersCompany($user, $alertRule);
    }

    private function belongsToUsersCompany(
        User $user,
        AlertRule $alertRule,
    ): bool {
        return $this->isSuperAdmin($user)
            || $user->company_id === $alertRule->company_id;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}

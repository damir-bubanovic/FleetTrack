<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('companies.view');
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        return $user->can('companies.view')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $company->id
            );
    }

    /**
     * Determine whether the user can create companies.
     */
    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user)
            && $user->can('companies.create');
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        return $user->can('companies.update')
            && (
                $this->isSuperAdmin($user)
                || $user->company_id === $company->id
            );
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        return $this->isSuperAdmin($user)
            && $user->can('companies.delete');
    }

    /**
     * Determine whether the user can restore the company.
     */
    public function restore(User $user, Company $company): bool
    {
        return $this->isSuperAdmin($user)
            && $user->can('companies.update');
    }

    /**
     * Determine whether the user can permanently delete the company.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        return $this->isSuperAdmin($user)
            && $user->can('companies.delete');
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

<?php

namespace App\Actions\Fleet;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Fleet;
use App\Models\User;

class CreateFleet
{
    /**
     * Create a new fleet.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Fleet
    {
        $isSuperAdmin = $user->hasRole(UserRole::SuperAdmin->value)
            && $user->company_id === null;

        if (! $isSuperAdmin) {
            $data['company_id'] = $user->company_id;
        }

        /** @var Company $company */
        $company = Company::query()
            ->findOrFail($data['company_id']);

        $data['company_id'] = $company->id;

        return Fleet::create($data);
    }
}

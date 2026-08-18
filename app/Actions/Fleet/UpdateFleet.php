<?php

namespace App\Actions\Fleet;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Fleet;
use App\Models\User;

class UpdateFleet
{
    /**
     * Update an existing fleet.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(
        User $user,
        Fleet $fleet,
        array $data
    ): Fleet {
        $isSuperAdmin = $user->hasRole(UserRole::SuperAdmin->value)
            && $user->company_id === null;

        if (! $isSuperAdmin) {
            unset($data['company_id']);
        } elseif (isset($data['company_id'])) {
            /** @var Company $company */
            $company = Company::query()
                ->findOrFail($data['company_id']);

            $data['company_id'] = $company->id;
        }

        $fleet->update($data);

        return $fleet->refresh();
    }
}

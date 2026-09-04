<?php

declare(strict_types=1);

namespace App\Actions\Geofence;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Geofence;
use App\Models\User;

class UpdateGeofence
{
    /**
     * Update an existing geofence.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(
        User $user,
        Geofence $geofence,
        array $data
    ): Geofence {
        $isSuperAdmin = $user->hasRole(UserRole::SuperAdmin->value);

        if (! $isSuperAdmin) {
            unset($data['company_id']);
        } elseif (isset($data['company_id'])) {
            /** @var Company $company */
            $company = Company::query()
                ->findOrFail($data['company_id']);

            $data['company_id'] = $company->id;
        }

        $geofence->update($data);

        return $geofence->refresh();
    }
}

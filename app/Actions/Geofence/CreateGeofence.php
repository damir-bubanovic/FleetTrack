<?php

declare(strict_types=1);

namespace App\Actions\Geofence;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Geofence;
use App\Models\User;

class CreateGeofence
{
    /**
     * Create a new geofence.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Geofence
    {
        $isSuperAdmin = $user->hasRole(UserRole::SuperAdmin->value);

        if (! $isSuperAdmin) {
            $data['company_id'] = $user->company_id;
        }

        /** @var Company $company */
        $company = Company::query()
            ->findOrFail($data['company_id']);

        $data['company_id'] = $company->id;

        return Geofence::create($data);
    }
}

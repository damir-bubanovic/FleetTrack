<?php

declare(strict_types=1);

namespace App\Actions\Geofence;

use App\Enums\UserRole;
use App\Events\GeofenceUpdated;
use App\Models\Company;
use App\Models\Geofence;
use App\Models\User;

class UpdateGeofence
{
    public function __construct(
        private readonly DetachVehicleFromGeofence $detachVehicle,
    ) {}

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
        $previousCompanyId = $geofence->company_id;

        if (! $isSuperAdmin) {
            unset($data['company_id']);
        } elseif (isset($data['company_id'])) {
            /** @var Company $company */
            $company = Company::query()
                ->findOrFail($data['company_id']);

            $data['company_id'] = $company->id;
        }

        if (
            isset($data['company_id'])
            && $data['company_id'] !== $previousCompanyId
        ) {
            $geofence->load('vehicles');

            foreach ($geofence->vehicles as $vehicle) {
                $this->detachVehicle->handle(
                    $geofence,
                    $vehicle,
                );
            }
        }

        $geofence->update($data);

        $geofence->refresh();

        GeofenceUpdated::dispatch($geofence);

        return $geofence;
    }
}

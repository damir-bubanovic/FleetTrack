<?php

declare(strict_types=1);

namespace App\Actions\Geofence;

use App\Events\VehicleAttachedToGeofence;
use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;

class AttachVehicleToGeofence
{
    /**
     * @throws AuthorizationException
     */
    public function handle(
        Geofence $geofence,
        Vehicle $vehicle,
    ): void {
        if ($geofence->company_id !== $vehicle->company_id) {
            throw new AuthorizationException(
                'The vehicle does not belong to the geofence company.',
            );
        }

        $changes = $geofence->vehicles()->syncWithoutDetaching([
            $vehicle->id,
        ]);

        if ($changes['attached'] !== []) {
            VehicleAttachedToGeofence::dispatch(
                $geofence,
                $vehicle,
            );
        }
    }
}

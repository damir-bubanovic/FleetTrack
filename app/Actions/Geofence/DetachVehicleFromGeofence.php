<?php

declare(strict_types=1);

namespace App\Actions\Geofence;

use App\Events\VehicleDetachedFromGeofence;
use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;

class DetachVehicleFromGeofence
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

        $detached = $geofence->vehicles()->detach($vehicle->id);

        if ($detached > 0) {
            VehicleDetachedFromGeofence::dispatch(
                $geofence,
                $vehicle,
            );
        }
    }
}

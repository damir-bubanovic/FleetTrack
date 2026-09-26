<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\VehicleDetachedFromGeofence;
use App\Jobs\DetachGeofenceFromDeviceInTraccar;

class SyncVehicleDetachedFromGeofenceToTraccar
{
    public function handle(VehicleDetachedFromGeofence $event): void
    {
        $traccarDeviceId = $event->vehicle->device?->traccar_device_id;

        if ($traccarDeviceId === null) {
            return;
        }

        DetachGeofenceFromDeviceInTraccar::dispatch(
            $event->geofence->id,
            $event->vehicle->id,
            $traccarDeviceId,
        );
    }
}

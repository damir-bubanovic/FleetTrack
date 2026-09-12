<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\VehicleDetachedFromGeofence;
use App\Jobs\DetachGeofenceFromDeviceInTraccar;

class SyncVehicleDetachedFromGeofenceToTraccar
{
    public function handle(VehicleDetachedFromGeofence $event): void
    {
        DetachGeofenceFromDeviceInTraccar::dispatch(
            $event->geofence->id,
            $event->vehicle->id,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\VehicleAttachedToGeofence;
use App\Jobs\AttachGeofenceToDeviceInTraccar;

class SyncVehicleAttachedToGeofenceToTraccar
{
    public function handle(VehicleAttachedToGeofence $event): void
    {
        AttachGeofenceToDeviceInTraccar::dispatch(
            $event->geofence->id,
            $event->vehicle->id,
        );
    }
}

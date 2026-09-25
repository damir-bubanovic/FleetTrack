<?php

namespace App\Listeners;

use App\Events\DeviceUpdated;
use App\Jobs\ReconcileDeviceGeofencesInTraccar;
use App\Jobs\UpdateDeviceInTraccar;

class SyncDeviceUpdatedToTraccar
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DeviceUpdated $event): void
    {
        UpdateDeviceInTraccar::dispatch($event->device);
        ReconcileDeviceGeofencesInTraccar::dispatch(
            deviceId: $event->device->id,
            previousVehicleId: $event->previousVehicleId,
        );
    }
}

<?php

namespace App\Listeners;

use App\Events\DeviceDeleted;
use App\Jobs\DeleteDeviceFromTraccar;

class SyncDeviceDeletedToTraccar
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
    public function handle(DeviceDeleted $event): void
    {
        if ($event->device->traccar_device_id === null) {
            return;
        }

        DeleteDeviceFromTraccar::dispatch(
            $event->device->id,
            $event->device->traccar_device_id,
        );
    }
}
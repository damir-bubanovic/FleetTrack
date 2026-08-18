<?php

namespace App\Listeners;

use App\Events\DeviceCreated;
use App\Jobs\SyncDeviceToTraccar;

class SyncDeviceCreatedToTraccar
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
    public function handle(DeviceCreated $event): void
    {
        SyncDeviceToTraccar::dispatch($event->device);
    }
}

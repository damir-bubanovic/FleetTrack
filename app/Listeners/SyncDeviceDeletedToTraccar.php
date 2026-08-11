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
        DeleteDeviceFromTraccar::dispatch($event->device);
    }
}
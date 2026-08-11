<?php

namespace App\Actions\Device;

use App\Events\DeviceDeleted;
use App\Models\Device;

class DeleteDevice
{
    /**
     * Delete the specified device.
     */
    public function handle(Device $device): void
    {
        event(new DeviceDeleted($device));

        $device->delete();
    }
}
<?php

namespace App\Actions\Device;

use App\Models\Device;

class DeleteDevice
{
    /**
     * Delete the specified device.
     */
    public function handle(Device $device): void
    {
        $device->delete();
    }
}

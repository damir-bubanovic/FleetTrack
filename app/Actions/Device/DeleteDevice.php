<?php

declare(strict_types=1);

namespace App\Actions\Device;

use App\Events\DeviceDeleted;
use App\Models\Device;

final class DeleteDevice
{
    public function handle(Device $device): void
    {
        $device->delete();

        DeviceDeleted::dispatch($device);
    }
}

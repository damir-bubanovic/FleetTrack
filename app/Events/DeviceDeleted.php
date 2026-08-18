<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceDeleted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Device $device,
    ) {}
}

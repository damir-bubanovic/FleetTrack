<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Alert\CreateDeviceOfflineAlert;
use App\Events\DeviceWentOffline;
use Illuminate\Contracts\Queue\ShouldQueue;

final class HandleDeviceWentOffline implements ShouldQueue
{
    public function __construct(
        private readonly CreateDeviceOfflineAlert $createDeviceOfflineAlert,
    ) {}

    public function handle(DeviceWentOffline $event): void
    {
        $this->createDeviceOfflineAlert->execute($event);
    }
}

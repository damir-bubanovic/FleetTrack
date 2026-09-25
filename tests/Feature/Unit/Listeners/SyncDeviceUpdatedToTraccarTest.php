<?php

declare(strict_types=1);

use App\Events\DeviceUpdated;
use App\Jobs\ReconcileDeviceGeofencesInTraccar;
use App\Jobs\UpdateDeviceInTraccar;
use App\Listeners\SyncDeviceUpdatedToTraccar;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('dispatches device update and geofence reconciliation jobs', function (): void {
    Queue::fake();

    $device = Device::factory()->create([
        'traccar_device_id' => 123,
    ]);

    $previousVehicleId = 456;

    $listener = new SyncDeviceUpdatedToTraccar;

    $listener->handle(new DeviceUpdated(
        $device,
        $previousVehicleId,
    ));

    Queue::assertPushed(
        UpdateDeviceInTraccar::class,
        function (UpdateDeviceInTraccar $job) use ($device): bool {
            return $job->device->id === $device->id;
        },
    );

    Queue::assertPushed(
        ReconcileDeviceGeofencesInTraccar::class,
        function (ReconcileDeviceGeofencesInTraccar $job) use (
            $device,
            $previousVehicleId,
        ): bool {
            return $job->deviceId === $device->id
                && $job->previousVehicleId === $previousVehicleId;
        },
    );
});

<?php

declare(strict_types=1);

use App\Events\VehicleDetachedFromGeofence;
use App\Jobs\DetachGeofenceFromDeviceInTraccar;
use App\Listeners\SyncVehicleDetachedFromGeofenceToTraccar;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('dispatches detach job with the current Traccar device id', function (): void {
    Queue::fake();

    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $geofence->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 456,
    ]);

    $listener = new SyncVehicleDetachedFromGeofenceToTraccar;

    $listener->handle(new VehicleDetachedFromGeofence(
        $geofence,
        $vehicle,
    ));

    Queue::assertPushed(
        DetachGeofenceFromDeviceInTraccar::class,
        function (DetachGeofenceFromDeviceInTraccar $job) use (
            $geofence,
            $vehicle,
            $device,
        ): bool {
            return $job->geofenceId === $geofence->id
                && $job->vehicleId === $vehicle->id
                && $job->traccarDeviceId === $device->traccar_device_id;
        },
    );
});

test('does not dispatch detach job when the vehicle has no device', function (): void {
    Queue::fake();

    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $listener = new SyncVehicleDetachedFromGeofenceToTraccar;

    $listener->handle(new VehicleDetachedFromGeofence(
        $geofence,
        $vehicle,
    ));

    Queue::assertNotPushed(DetachGeofenceFromDeviceInTraccar::class);
});

test('does not dispatch detach job when the vehicle device is not synced with Traccar', function (): void {
    Queue::fake();

    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    Device::factory()->create([
        'company_id' => $geofence->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => null,
    ]);

    $listener = new SyncVehicleDetachedFromGeofenceToTraccar;

    $listener->handle(new VehicleDetachedFromGeofence(
        $geofence,
        $vehicle,
    ));

    Queue::assertNotPushed(DetachGeofenceFromDeviceInTraccar::class);
});

<?php

declare(strict_types=1);

use App\Data\Traccar\DeviceData;
use App\Jobs\AttachGeofenceToDeviceInTraccar;
use App\Jobs\SyncDeviceToTraccar;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Services\Traccar\TraccarDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('syncs an unsynced device to Traccar', function (): void {
    Queue::fake();

    $device = Device::factory()->create([
        'traccar_device_id' => null,
        'last_sync_at' => null,
    ]);

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock) use ($device): void {
            $mock->shouldReceive('create')
                ->once()
                ->with([
                    'name' => $device->name,
                    'uniqueId' => $device->unique_id,
                    'model' => $device->model,
                    'phone' => $device->phone,
                ])
                ->andReturn(new DeviceData(
                    id: 123,
                    name: $device->name,
                    uniqueId: $device->unique_id,
                    model: $device->model,
                    phone: $device->phone,
                ));
        },
    );

    $job = new SyncDeviceToTraccar($device);

    $job->handle($service);

    $device->refresh();

    expect($device->traccar_device_id)->toBe(123)
        ->and($device->last_sync_at)->not->toBeNull();
});

test('does nothing when the device is already synced', function (): void {
    $device = Device::factory()->create([
        'traccar_device_id' => 123,
    ]);

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('create');
        },
    );

    $job = new SyncDeviceToTraccar($device);

    $job->handle($service);

    expect($device->fresh()?->traccar_device_id)->toBe(123);
});

test('does nothing when the device no longer exists', function (): void {
    $device = Device::factory()->create([
        'traccar_device_id' => null,
    ]);

    $job = new SyncDeviceToTraccar($device);

    $device->delete();

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('create');
        },
    );

    $job->handle($service);
});

test('queues association synchronization for vehicle geofences after device sync', function (): void {
    Queue::fake();

    $vehicle = Vehicle::factory()->create();

    $device = Device::factory()->create([
        'company_id' => $vehicle->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => null,
        'last_sync_at' => null,
    ]);

    $firstGeofence = Geofence::factory()->create([
        'company_id' => $vehicle->company_id,
    ]);

    $secondGeofence = Geofence::factory()->create([
        'company_id' => $vehicle->company_id,
    ]);

    $vehicle->geofences()->attach([
        $firstGeofence->id,
        $secondGeofence->id,
    ]);

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock) use ($device): void {
            $mock->shouldReceive('create')
                ->once()
                ->andReturn(new DeviceData(
                    id: 123,
                    name: $device->name,
                    uniqueId: $device->unique_id,
                    model: $device->model,
                    phone: $device->phone,
                ));
        },
    );

    $job = new SyncDeviceToTraccar($device);

    $job->handle($service);

    Queue::assertPushed(
        AttachGeofenceToDeviceInTraccar::class,
        2,
    );

    Queue::assertPushed(
        AttachGeofenceToDeviceInTraccar::class,
        fn (AttachGeofenceToDeviceInTraccar $job): bool => $job->geofenceId === $firstGeofence->id
            && $job->vehicleId === $vehicle->id,
    );

    Queue::assertPushed(
        AttachGeofenceToDeviceInTraccar::class,
        fn (AttachGeofenceToDeviceInTraccar $job): bool => $job->geofenceId === $secondGeofence->id
            && $job->vehicleId === $vehicle->id,
    );
});

test('syncs a device without a vehicle without queuing association synchronization', function (): void {
    Queue::fake();

    $device = Device::factory()->create([
        'vehicle_id' => null,
        'traccar_device_id' => null,
        'last_sync_at' => null,
    ]);

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock) use ($device): void {
            $mock->shouldReceive('create')
                ->once()
                ->andReturn(new DeviceData(
                    id: 123,
                    name: $device->name,
                    uniqueId: $device->unique_id,
                    model: $device->model,
                    phone: $device->phone,
                ));
        },
    );

    $job = new SyncDeviceToTraccar($device);

    $job->handle($service);

    expect($device->fresh()?->traccar_device_id)->toBe(123);

    Queue::assertNotPushed(
        AttachGeofenceToDeviceInTraccar::class,
    );
});

<?php

declare(strict_types=1);

use App\Jobs\AttachGeofenceToDeviceInTraccar;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('attaches a synced geofence to a synced vehicle device in Traccar', function (): void {
    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $geofence->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 456,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('attachDevice')
                ->once()
                ->with(123, 456);
        },
    );

    $job = new AttachGeofenceToDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
    );

    $job->handle($service);

    expect($device->exists)->toBeTrue();
});

test('does nothing when the geofence no longer exists', function (): void {
    $vehicle = Vehicle::factory()->create();

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('attachDevice');
        },
    );

    $job = new AttachGeofenceToDeviceInTraccar(
        geofenceId: 999999,
        vehicleId: $vehicle->id,
    );

    $job->handle($service);
});

test('does nothing when the vehicle no longer exists', function (): void {
    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('attachDevice');
        },
    );

    $job = new AttachGeofenceToDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: 999999,
    );

    $job->handle($service);
});

test('does nothing when the vehicle is no longer attached to the geofence', function (): void {
    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    Device::factory()->create([
        'company_id' => $geofence->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 456,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('attachDevice');
        },
    );

    $job = new AttachGeofenceToDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
    );

    $job->handle($service);
});

test('does nothing when the geofence is not synced with Traccar', function (): void {
    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => null,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    Device::factory()->create([
        'company_id' => $geofence->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 456,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('attachDevice');
        },
    );

    $job = new AttachGeofenceToDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
    );

    $job->handle($service);
});

test('does nothing when the vehicle has no device', function (): void {
    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('attachDevice');
        },
    );

    $job = new AttachGeofenceToDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
    );

    $job->handle($service);
});

test('does nothing when the vehicle device is not synced with Traccar', function (): void {
    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    Device::factory()->create([
        'company_id' => $geofence->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => null,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('attachDevice');
        },
    );

    $job = new AttachGeofenceToDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
    );

    $job->handle($service);
});

test('rethrows Traccar exceptions so the queue can retry the job', function (): void {
    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    Device::factory()->create([
        'company_id' => $geofence->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 456,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('attachDevice')
                ->once()
                ->with(123, 456)
                ->andThrow(new RuntimeException('Traccar unavailable'));
        },
    );

    $job = new AttachGeofenceToDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
    );

    expect(
        fn () => $job->handle($service)
    )->toThrow(
        RuntimeException::class,
        'Traccar unavailable',
    );
});

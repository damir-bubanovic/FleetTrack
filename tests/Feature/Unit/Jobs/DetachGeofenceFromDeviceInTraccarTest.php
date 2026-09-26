<?php

declare(strict_types=1);

use App\Jobs\DetachGeofenceFromDeviceInTraccar;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('detaches a synced geofence from the original Traccar device', function (): void {
    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('detachDevice')
                ->once()
                ->with(123, 456);
        },
    );

    $job = new DetachGeofenceFromDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
        traccarDeviceId: 456,
    );

    $job->handle($service);
});

test('does nothing when the geofence no longer exists', function (): void {
    $vehicle = Vehicle::factory()->create();

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('detachDevice');
        },
    );

    $job = new DetachGeofenceFromDeviceInTraccar(
        geofenceId: 999999,
        vehicleId: $vehicle->id,
        traccarDeviceId: 456,
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
            $mock->shouldNotReceive('detachDevice');
        },
    );

    $job = new DetachGeofenceFromDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: 999999,
        traccarDeviceId: 456,
    );

    $job->handle($service);
});

test('does nothing when the vehicle has been reattached before the job runs', function (): void {
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
            $mock->shouldNotReceive('detachDevice');
        },
    );

    $job = new DetachGeofenceFromDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
        traccarDeviceId: 456,
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

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('detachDevice');
        },
    );

    $job = new DetachGeofenceFromDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
        traccarDeviceId: 456,
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

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('detachDevice')
                ->once()
                ->with(123, 456)
                ->andThrow(new RuntimeException('Traccar unavailable'));
        },
    );

    $job = new DetachGeofenceFromDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
        traccarDeviceId: 456,
    );

    expect(
        fn () => $job->handle($service)
    )->toThrow(
        RuntimeException::class,
        'Traccar unavailable',
    );
});

test('detaches from the original device when it is reassigned before the job runs', function (): void {
    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $otherVehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $geofence->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 456,
    ]);

    $job = new DetachGeofenceFromDeviceInTraccar(
        geofenceId: $geofence->id,
        vehicleId: $vehicle->id,
        traccarDeviceId: $device->traccar_device_id,
    );

    $device->update([
        'vehicle_id' => $otherVehicle->id,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('detachDevice')
                ->once()
                ->with(123, 456);
        },
    );

    $job->handle($service);
});

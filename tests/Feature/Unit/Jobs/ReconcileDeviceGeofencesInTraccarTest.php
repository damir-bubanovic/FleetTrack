<?php

declare(strict_types=1);

use App\Jobs\ReconcileDeviceGeofencesInTraccar;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('reconciles geofences when device is reassigned to another vehicle', function (): void {
    $oldVehicle = Vehicle::factory()->create();
    $newVehicle = Vehicle::factory()->create([
        'company_id' => $oldVehicle->company_id,
    ]);

    $oldOnlyGeofence = Geofence::factory()->create([
        'company_id' => $oldVehicle->company_id,
        'traccar_geofence_id' => 101,
    ]);

    $sharedGeofence = Geofence::factory()->create([
        'company_id' => $oldVehicle->company_id,
        'traccar_geofence_id' => 102,
    ]);

    $newOnlyGeofence = Geofence::factory()->create([
        'company_id' => $oldVehicle->company_id,
        'traccar_geofence_id' => 103,
    ]);

    $oldVehicle->geofences()->attach([
        $oldOnlyGeofence->id,
        $sharedGeofence->id,
    ]);

    $newVehicle->geofences()->attach([
        $sharedGeofence->id,
        $newOnlyGeofence->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $oldVehicle->company_id,
        'vehicle_id' => $newVehicle->id,
        'traccar_device_id' => 456,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('detachDevice')
                ->once()
                ->with(101, 456);

            $mock->shouldReceive('attachDevice')
                ->once()
                ->with(103, 456);

            $mock->shouldNotReceive('detachDevice')
                ->with(102, 456);

            $mock->shouldNotReceive('attachDevice')
                ->with(102, 456);
        },
    );

    $job = new ReconcileDeviceGeofencesInTraccar(
        deviceId: $device->id,
        previousVehicleId: $oldVehicle->id,
    );

    $job->handle($service);
});

test('removes previous vehicle geofences when device is unassigned', function (): void {
    $vehicle = Vehicle::factory()->create();

    $firstGeofence = Geofence::factory()->create([
        'company_id' => $vehicle->company_id,
        'traccar_geofence_id' => 101,
    ]);

    $secondGeofence = Geofence::factory()->create([
        'company_id' => $vehicle->company_id,
        'traccar_geofence_id' => 102,
    ]);

    $vehicle->geofences()->attach([
        $firstGeofence->id,
        $secondGeofence->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $vehicle->company_id,
        'vehicle_id' => null,
        'traccar_device_id' => 456,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('detachDevice')
                ->once()
                ->with(101, 456);

            $mock->shouldReceive('detachDevice')
                ->once()
                ->with(102, 456);

            $mock->shouldNotReceive('attachDevice');
        },
    );

    $job = new ReconcileDeviceGeofencesInTraccar(
        deviceId: $device->id,
        previousVehicleId: $vehicle->id,
    );

    $job->handle($service);
});

test('adds new vehicle geofences when previously unassigned device is assigned', function (): void {
    $vehicle = Vehicle::factory()->create();

    $geofence = Geofence::factory()->create([
        'company_id' => $vehicle->company_id,
        'traccar_geofence_id' => 101,
    ]);

    $vehicle->geofences()->attach($geofence->id);

    $device = Device::factory()->create([
        'company_id' => $vehicle->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 456,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('attachDevice')
                ->once()
                ->with(101, 456);

            $mock->shouldNotReceive('detachDevice');
        },
    );

    $job = new ReconcileDeviceGeofencesInTraccar(
        deviceId: $device->id,
        previousVehicleId: null,
    );

    $job->handle($service);
});

test('does nothing when vehicle assignment has not changed', function (): void {
    $vehicle = Vehicle::factory()->create();

    $geofence = Geofence::factory()->create([
        'company_id' => $vehicle->company_id,
        'traccar_geofence_id' => 101,
    ]);

    $vehicle->geofences()->attach($geofence->id);

    $device = Device::factory()->create([
        'company_id' => $vehicle->company_id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 456,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('attachDevice');
            $mock->shouldNotReceive('detachDevice');
        },
    );

    $job = new ReconcileDeviceGeofencesInTraccar(
        deviceId: $device->id,
        previousVehicleId: $vehicle->id,
    );

    $job->handle($service);
});

test('does nothing when device is not synced with Traccar', function (): void {
    $oldVehicle = Vehicle::factory()->create();
    $newVehicle = Vehicle::factory()->create([
        'company_id' => $oldVehicle->company_id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $oldVehicle->company_id,
        'vehicle_id' => $newVehicle->id,
        'traccar_device_id' => null,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('attachDevice');
            $mock->shouldNotReceive('detachDevice');
        },
    );

    $job = new ReconcileDeviceGeofencesInTraccar(
        deviceId: $device->id,
        previousVehicleId: $oldVehicle->id,
    );

    $job->handle($service);
});

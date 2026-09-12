<?php

declare(strict_types=1);

use App\Actions\Geofence\AttachVehicleToGeofence;
use App\Actions\Geofence\DetachVehicleFromGeofence;
use App\Events\VehicleAttachedToGeofence;
use App\Events\VehicleDetachedFromGeofence;
use App\Jobs\AttachGeofenceToDeviceInTraccar;
use App\Jobs\DetachGeofenceFromDeviceInTraccar;
use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('attaches a vehicle to a geofence', function (): void {
    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    app(AttachVehicleToGeofence::class)
        ->handle($geofence, $vehicle);

    expect(
        $geofence->vehicles()
            ->whereKey($vehicle->id)
            ->exists()
    )->toBeTrue();
});

test('attaching the same vehicle twice is idempotent', function (): void {
    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $action = app(AttachVehicleToGeofence::class);

    $action->handle($geofence, $vehicle);
    $action->handle($geofence, $vehicle);

    expect(
        $geofence->vehicles()
            ->whereKey($vehicle->id)
            ->count()
    )->toBe(1);
});

test('prevents attaching a vehicle from another company', function (): void {
    $geofence = Geofence::factory()->create();
    $vehicle = Vehicle::factory()->create();

    expect(
        fn () => app(AttachVehicleToGeofence::class)
            ->handle($geofence, $vehicle)
    )->toThrow(AuthorizationException::class);

    expect(
        $geofence->vehicles()
            ->whereKey($vehicle->id)
            ->exists()
    )->toBeFalse();
});

test('detaches a vehicle from a geofence', function (): void {
    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    app(DetachVehicleFromGeofence::class)
        ->handle($geofence, $vehicle);

    expect(
        $geofence->vehicles()
            ->whereKey($vehicle->id)
            ->exists()
    )->toBeFalse();
});

test('detaching a vehicle that is not attached is idempotent', function (): void {
    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    app(DetachVehicleFromGeofence::class)
        ->handle($geofence, $vehicle);

    expect(
        $geofence->vehicles()
            ->whereKey($vehicle->id)
            ->exists()
    )->toBeFalse();
});

test('prevents detaching a vehicle from another company', function (): void {
    $geofence = Geofence::factory()->create();
    $vehicle = Vehicle::factory()->create();

    expect(
        fn () => app(DetachVehicleFromGeofence::class)
            ->handle($geofence, $vehicle)
    )->toThrow(AuthorizationException::class);
});

test('dispatches an event when a vehicle is attached', function (): void {
    Event::fake([
        VehicleAttachedToGeofence::class,
    ]);

    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    app(AttachVehicleToGeofence::class)
        ->handle($geofence, $vehicle);

    Event::assertDispatched(
        VehicleAttachedToGeofence::class,
        fn (VehicleAttachedToGeofence $event): bool => $event->geofence->is($geofence)
            && $event->vehicle->is($vehicle),
    );
});

test('does not dispatch another attach event when the vehicle is already attached', function (): void {
    Event::fake([
        VehicleAttachedToGeofence::class,
    ]);

    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $action = app(AttachVehicleToGeofence::class);

    $action->handle($geofence, $vehicle);
    $action->handle($geofence, $vehicle);

    Event::assertDispatchedTimes(
        VehicleAttachedToGeofence::class,
        1,
    );
});

test('queues Traccar synchronization when a vehicle is attached', function (): void {
    Queue::fake();

    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    app(AttachVehicleToGeofence::class)
        ->handle($geofence, $vehicle);

    Queue::assertPushed(
        AttachGeofenceToDeviceInTraccar::class,
        fn (AttachGeofenceToDeviceInTraccar $job): bool => $job->geofenceId === $geofence->id
            && $job->vehicleId === $vehicle->id,
    );
});

test('dispatches an event when a vehicle is detached', function (): void {
    Event::fake([
        VehicleDetachedFromGeofence::class,
    ]);

    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    app(DetachVehicleFromGeofence::class)
        ->handle($geofence, $vehicle);

    Event::assertDispatched(
        VehicleDetachedFromGeofence::class,
        fn (VehicleDetachedFromGeofence $event): bool => $event->geofence->is($geofence)
            && $event->vehicle->is($vehicle),
    );
});

test('does not dispatch a detach event when the vehicle is not attached', function (): void {
    Event::fake([
        VehicleDetachedFromGeofence::class,
    ]);

    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    app(DetachVehicleFromGeofence::class)
        ->handle($geofence, $vehicle);

    Event::assertNotDispatched(
        VehicleDetachedFromGeofence::class,
    );
});

test('queues Traccar synchronization when a vehicle is detached', function (): void {
    Queue::fake();

    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    app(DetachVehicleFromGeofence::class)
        ->handle($geofence, $vehicle);

    Queue::assertPushed(
        DetachGeofenceFromDeviceInTraccar::class,
        fn (DetachGeofenceFromDeviceInTraccar $job): bool => $job->geofenceId === $geofence->id
            && $job->vehicleId === $vehicle->id,
    );
});

<?php

declare(strict_types=1);

use App\Data\Traccar\GeofenceData;
use App\Jobs\AttachGeofenceToDeviceInTraccar;
use App\Jobs\SyncGeofenceToTraccar;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'traccar.url' => 'https://traccar.test',
        'traccar.username' => 'admin',
        'traccar.password' => 'admin',
        'traccar.timeout' => 30,
        'traccar.verify_ssl' => false,
    ]);
});

test('syncs an unsynced geofence to traccar', function (): void {
    Http::fake([
        'https://traccar.test/api/geofences' => Http::response([
            'id' => 123,
            'name' => 'Warehouse',
            'description' => 'Main warehouse',
            'area' => 'CIRCLE (45.8150 15.9819, 100)',
            'attributes' => [],
        ], 200),
    ]);

    $geofence = Geofence::factory()->create([
        'name' => 'Warehouse',
        'description' => 'Main warehouse',
        'area' => 'CIRCLE (45.8150 15.9819, 100)',
        'traccar_geofence_id' => null,
        'last_sync_at' => null,
    ]);

    $job = new SyncGeofenceToTraccar($geofence->id);

    $job->handle(app(TraccarGeofenceService::class));

    $geofence->refresh();

    expect($geofence->traccar_geofence_id)
        ->toBe(123)
        ->and($geofence->last_sync_at)
        ->not->toBeNull();

    Http::assertSent(function ($request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://traccar.test/api/geofences'
            && $request['name'] === 'Warehouse'
            && $request['description'] === 'Main warehouse'
            && $request['area'] === 'CIRCLE (45.8150 15.9819, 100)';
    });

    Http::assertSentCount(1);
});

test('does not sync an already synced geofence again', function (): void {
    Http::fake();

    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
        'last_sync_at' => now(),
    ]);

    $job = new SyncGeofenceToTraccar($geofence->id);

    $job->handle(app(TraccarGeofenceService::class));

    Http::assertNothingSent();

    expect($geofence->refresh()->traccar_geofence_id)->toBe(123);
});

test('exits cleanly when the geofence no longer exists', function (): void {
    Http::fake();

    $geofence = Geofence::factory()->create();

    $geofenceId = $geofence->id;

    $geofence->delete();

    $job = new SyncGeofenceToTraccar($geofenceId);

    $job->handle(app(TraccarGeofenceService::class));

    Http::assertNothingSent();
});

test('queues association synchronization for attached vehicles after geofence sync', function (): void {
    Queue::fake();

    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => null,
        'last_sync_at' => null,
    ]);

    $firstVehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $secondVehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $geofence->vehicles()->attach([
        $firstVehicle->id,
        $secondVehicle->id,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('create')
                ->once()
                ->andReturn(new GeofenceData(
                    id: 123,
                    name: 'Test Geofence',
                    area: 'CIRCLE (45.8150 15.9819, 100)',
                    description: null,
                ));
        },
    );

    $job = new SyncGeofenceToTraccar(
        geofenceId: $geofence->id,
    );

    $job->handle($service);

    Queue::assertPushed(
        AttachGeofenceToDeviceInTraccar::class,
        2,
    );

    Queue::assertPushed(
        AttachGeofenceToDeviceInTraccar::class,
        fn (AttachGeofenceToDeviceInTraccar $job): bool => $job->geofenceId === $geofence->id
            && $job->vehicleId === $firstVehicle->id,
    );

    Queue::assertPushed(
        AttachGeofenceToDeviceInTraccar::class,
        fn (AttachGeofenceToDeviceInTraccar $job): bool => $job->geofenceId === $geofence->id
            && $job->vehicleId === $secondVehicle->id,
    );
});

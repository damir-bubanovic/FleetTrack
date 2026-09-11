<?php

declare(strict_types=1);

use App\Jobs\SyncGeofenceToTraccar;
use App\Jobs\UpdateGeofenceInTraccar;
use App\Models\Geofence;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

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

test('updates a synced geofence in traccar', function (): void {
    Http::fake([
        'https://traccar.test/api/geofences/123' => Http::sequence()
            ->push([
                'id' => 123,
                'name' => 'Old Warehouse',
                'description' => 'Old description',
                'area' => 'CIRCLE (45.8150 15.9819, 50)',
                'attributes' => [],
            ])
            ->push([
                'id' => 123,
                'name' => 'Updated Warehouse',
                'description' => 'Updated description',
                'area' => 'CIRCLE (45.8150 15.9819, 100)',
                'attributes' => [],
            ]),
    ]);

    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
        'name' => 'Updated Warehouse',
        'description' => 'Updated description',
        'area' => 'CIRCLE (45.8150 15.9819, 100)',
        'last_sync_at' => null,
    ]);

    $job = new UpdateGeofenceInTraccar($geofence->id);

    $job->handle(app(TraccarGeofenceService::class));

    expect($geofence->refresh()->last_sync_at)
        ->not->toBeNull();

    Http::assertSentCount(2);

    Http::assertSent(function ($request): bool {
        return $request->method() === 'PUT'
            && $request->url() === 'https://traccar.test/api/geofences/123'
            && $request['name'] === 'Updated Warehouse'
            && $request['description'] === 'Updated description'
            && $request['area'] === 'CIRCLE (45.8150 15.9819, 100)';
    });
});

test('queues creation sync when geofence has not been synced yet', function (): void {
    Queue::fake();

    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => null,
        'last_sync_at' => null,
    ]);

    $job = new UpdateGeofenceInTraccar($geofence->id);

    $job->handle(app(TraccarGeofenceService::class));

    Queue::assertPushed(
        SyncGeofenceToTraccar::class,
        fn (SyncGeofenceToTraccar $job): bool => $job->geofenceId === $geofence->id,
    );

    expect($geofence->refresh()->last_sync_at)->toBeNull();
});

test('exits cleanly when the geofence no longer exists', function (): void {
    Http::fake();

    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
    ]);

    $geofenceId = $geofence->id;

    $geofence->delete();

    $job = new UpdateGeofenceInTraccar($geofenceId);

    $job->handle(app(TraccarGeofenceService::class));

    Http::assertNothingSent();
});

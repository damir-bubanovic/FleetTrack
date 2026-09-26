<?php

declare(strict_types=1);

use App\Jobs\DeleteGeofenceFromTraccar;
use App\Models\Geofence;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

beforeEach(function (): void {
    config([
        'traccar.url' => 'https://traccar.test',
        'traccar.username' => 'admin',
        'traccar.password' => 'admin',
        'traccar.timeout' => 30,
        'traccar.verify_ssl' => false,
    ]);
});

test('deletes a geofence from traccar', function (): void {
    Http::fake([
        'https://traccar.test/api/geofences/123' => Http::response(
            body: null,
            status: 204,
        ),
    ]);

    $job = new DeleteGeofenceFromTraccar(
        geofenceId: 10,
        traccarGeofenceId: 123,
    );

    $job->handle(app(TraccarGeofenceService::class));

    Http::assertSent(function ($request): bool {
        return $request->method() === 'DELETE'
            && $request->url() === 'https://traccar.test/api/geofences/123';
    });

    Http::assertSentCount(1);
});

test('does nothing when the geofence still exists', function (): void {
    $geofence = Geofence::factory()->create([
        'traccar_geofence_id' => 123,
    ]);

    $service = $this->mock(
        TraccarGeofenceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('delete');
        },
    );

    $job = new DeleteGeofenceFromTraccar(
        geofenceId: $geofence->id,
        traccarGeofenceId: 123,
    );

    $job->handle($service);
});

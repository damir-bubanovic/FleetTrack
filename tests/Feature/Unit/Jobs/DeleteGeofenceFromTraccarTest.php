<?php

declare(strict_types=1);

use App\Jobs\DeleteGeofenceFromTraccar;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Support\Facades\Http;

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

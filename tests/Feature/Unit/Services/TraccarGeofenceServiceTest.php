<?php

declare(strict_types=1);

use App\Data\Traccar\GeofenceData;
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

test('can fetch single geofence', function (): void {
    Http::fake([
        'https://traccar.test/api/geofences/1' => Http::response([
            'id' => 1,
            'name' => 'Warehouse',
            'description' => 'Main warehouse',
            'area' => 'CIRCLE (45.8150 15.9819, 100)',
            'attributes' => [],
        ], 200),
    ]);

    $geofence = app(TraccarGeofenceService::class)->find(1);

    expect($geofence)
        ->toBeInstanceOf(GeofenceData::class)
        ->and($geofence->id)
        ->toBe(1)
        ->and($geofence->name)
        ->toBe('Warehouse')
        ->and($geofence->description)
        ->toBe('Main warehouse')
        ->and($geofence->area)
        ->toBe('CIRCLE (45.8150 15.9819, 100)');

    Http::assertSentCount(1);
});

test('can create geofence', function (): void {
    Http::fake([
        'https://traccar.test/api/geofences' => Http::response([
            'id' => 10,
            'name' => 'Warehouse',
            'description' => 'Main warehouse',
            'area' => 'CIRCLE (45.8150 15.9819, 100)',
            'attributes' => [],
        ], 200),
    ]);

    $geofence = app(TraccarGeofenceService::class)->create([
        'name' => 'Warehouse',
        'description' => 'Main warehouse',
        'area' => 'CIRCLE (45.8150 15.9819, 100)',
    ]);

    expect($geofence)
        ->toBeInstanceOf(GeofenceData::class)
        ->and($geofence->id)
        ->toBe(10)
        ->and($geofence->name)
        ->toBe('Warehouse')
        ->and($geofence->description)
        ->toBe('Main warehouse')
        ->and($geofence->area)
        ->toBe('CIRCLE (45.8150 15.9819, 100)');

    Http::assertSent(function ($request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://traccar.test/api/geofences'
            && $request['name'] === 'Warehouse'
            && $request['description'] === 'Main warehouse'
            && $request['area'] === 'CIRCLE (45.8150 15.9819, 100)';
    });

    Http::assertSentCount(1);
});

test('can update geofence', function (): void {
    Http::fake([
        'https://traccar.test/api/geofences/10' => Http::sequence()
            ->push([
                'id' => 10,
                'name' => 'Warehouse',
                'description' => 'Main warehouse',
                'area' => 'CIRCLE (45.8150 15.9819, 100)',
                'calendarId' => 0,
                'attributes' => [],
            ], 200)
            ->push([
                'id' => 10,
                'name' => 'Updated Warehouse',
                'description' => 'Updated description',
                'area' => 'CIRCLE (45.8150 15.9819, 200)',
                'calendarId' => 0,
                'attributes' => [],
            ], 200),
    ]);

    $geofence = app(TraccarGeofenceService::class)->update(10, [
        'name' => 'Updated Warehouse',
        'description' => 'Updated description',
        'area' => 'CIRCLE (45.8150 15.9819, 200)',
    ]);

    expect($geofence)
        ->toBeInstanceOf(GeofenceData::class)
        ->and($geofence->id)
        ->toBe(10)
        ->and($geofence->name)
        ->toBe('Updated Warehouse')
        ->and($geofence->description)
        ->toBe('Updated description')
        ->and($geofence->area)
        ->toBe('CIRCLE (45.8150 15.9819, 200)');

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'PUT') {
            return false;
        }

        return $request->url() === 'https://traccar.test/api/geofences/10'
            && $request['id'] === 10
            && $request['name'] === 'Updated Warehouse'
            && $request['description'] === 'Updated description'
            && $request['area'] === 'CIRCLE (45.8150 15.9819, 200)'
            && $request['calendarId'] === 0
            && is_object($request['attributes']);
    });

    Http::assertSentCount(2);
});

test('can delete geofence', function (): void {
    Http::fake([
        'https://traccar.test/api/geofences/10' => Http::response(null, 204),
    ]);

    app(TraccarGeofenceService::class)->delete(10);

    Http::assertSent(function ($request): bool {
        return $request->method() === 'DELETE'
            && $request->url() === 'https://traccar.test/api/geofences/10';
    });

    Http::assertSentCount(1);
});

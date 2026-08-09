<?php

use App\Services\Traccar\TraccarService;
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

test('can fetch devices', function (): void {
    Http::fake([
        'https://traccar.test/api/devices' => Http::response([
            [
                'id' => 1,
                'name' => 'Truck 1',
            ],
        ], 200),
    ]);

    $response = app(TraccarService::class)->devices();

    expect($response->successful())->toBeTrue();
    expect($response->json())
        ->toHaveCount(1)
        ->and($response->json()[0]['id'])->toBe(1);

    Http::assertSentCount(1);
});

test('can fetch single device', function (): void {
    Http::fake([
        'https://traccar.test/api/devices/1' => Http::response([
            'id' => 1,
            'name' => 'Truck 1',
        ], 200),
    ]);

    $response = app(TraccarService::class)->device(1);

    expect($response->successful())->toBeTrue();
    expect($response->json('id'))->toBe(1);

    Http::assertSentCount(1);
});

test('can create device', function (): void {
    Http::fake([
        'https://traccar.test/api/devices' => Http::response([
            'id' => 10,
        ], 200),
    ]);

    $response = app(TraccarService::class)->createDevice([
        'name' => 'Truck',
        'uniqueId' => '123456789',
    ]);

    expect($response->successful())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['name'] === 'Truck'
            && $request['uniqueId'] === '123456789';
    });
});

test('can update device', function (): void {
    Http::fake([
        'https://traccar.test/api/devices/10' => Http::response([], 200),
    ]);

    $response = app(TraccarService::class)->updateDevice(10, [
        'name' => 'Updated Truck',
    ]);

    expect($response->successful())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'PUT'
            && $request['name'] === 'Updated Truck';
    });
});

test('can delete device', function (): void {
    Http::fake([
        'https://traccar.test/api/devices/10' => Http::response([], 204),
    ]);

    $response = app(TraccarService::class)->deleteDevice(10);

    expect($response->status())->toBe(204);

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE';
    });
});
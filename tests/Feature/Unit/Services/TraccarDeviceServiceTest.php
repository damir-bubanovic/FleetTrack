<?php

use App\Data\Traccar\DeviceData;
use App\Services\Traccar\TraccarDeviceService;
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
                'uniqueId' => 'TRUCK-001',
                'status' => 'online',
            ],
        ], 200),
    ]);

    $devices = app(TraccarDeviceService::class)->all();

    expect($devices)
        ->toHaveCount(1)
        ->and($devices[0])
        ->toBeInstanceOf(DeviceData::class)
        ->and($devices[0]->id)
        ->toBe(1)
        ->and($devices[0]->name)
        ->toBe('Truck 1')
        ->and($devices[0]->uniqueId)
        ->toBe('TRUCK-001')
        ->and($devices[0]->status)
        ->toBe('online');

    Http::assertSentCount(1);
});

test('can fetch single device', function (): void {
    Http::fake([
        'https://traccar.test/api/devices/1' => Http::response([
            'id' => 1,
            'name' => 'Truck 1',
            'uniqueId' => 'TRUCK-001',
            'status' => 'offline',
        ], 200),
    ]);

    $device = app(TraccarDeviceService::class)->find(1);

    expect($device)
        ->toBeInstanceOf(DeviceData::class)
        ->and($device->id)
        ->toBe(1)
        ->and($device->name)
        ->toBe('Truck 1')
        ->and($device->uniqueId)
        ->toBe('TRUCK-001');

    Http::assertSentCount(1);
});

test('can create device', function (): void {
    Http::fake([
        'https://traccar.test/api/devices' => Http::response([
            'id' => 10,
            'name' => 'Truck',
            'uniqueId' => '123456789',
            'status' => 'offline',
        ], 200),
    ]);

    $device = app(TraccarDeviceService::class)->create([
        'name' => 'Truck',
        'uniqueId' => '123456789',
    ]);

    expect($device)
        ->toBeInstanceOf(DeviceData::class)
        ->and($device->id)
        ->toBe(10)
        ->and($device->name)
        ->toBe('Truck')
        ->and($device->uniqueId)
        ->toBe('123456789');

    Http::assertSent(function ($request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://traccar.test/api/devices'
            && $request['name'] === 'Truck'
            && $request['uniqueId'] === '123456789';
    });
});

test('can update device', function (): void {
    Http::fake([
        'https://traccar.test/api/devices/10' => Http::response([
            'id' => 10,
            'name' => 'Updated Truck',
            'uniqueId' => '123456789',
            'status' => 'offline',
        ], 200),
    ]);

    $device = app(TraccarDeviceService::class)->update(10, [
        'name' => 'Updated Truck',
        'uniqueId' => '123456789',
    ]);

    expect($device)
        ->toBeInstanceOf(DeviceData::class)
        ->and($device->id)
        ->toBe(10)
        ->and($device->name)
        ->toBe('Updated Truck');

    Http::assertSent(function ($request): bool {
        return $request->method() === 'PUT'
            && $request->url() === 'https://traccar.test/api/devices/10'
            && $request['name'] === 'Updated Truck'
            && $request['uniqueId'] === '123456789';
    });
});

test('can delete device', function (): void {
    Http::fake([
        'https://traccar.test/api/devices/10' => Http::response(null, 204),
    ]);

    app(TraccarDeviceService::class)->delete(10);

    Http::assertSent(function ($request): bool {
        return $request->method() === 'DELETE'
            && $request->url() === 'https://traccar.test/api/devices/10';
    });

    Http::assertSentCount(1);
});
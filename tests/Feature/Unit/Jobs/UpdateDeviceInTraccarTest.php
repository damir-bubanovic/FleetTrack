<?php

declare(strict_types=1);

use App\Data\Traccar\DeviceData;
use App\Jobs\UpdateDeviceInTraccar;
use App\Models\Device;
use App\Services\Traccar\TraccarDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('updates a synced device in Traccar', function (): void {
    $device = Device::factory()->create([
        'traccar_device_id' => 123,
        'last_sync_at' => null,
    ]);

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock) use ($device): void {
            $mock->shouldReceive('update')
                ->once()
                ->with(123, [
                    'name' => $device->name,
                    'uniqueId' => $device->unique_id,
                    'model' => $device->model,
                    'phone' => $device->phone,
                ])
                ->andReturn(new DeviceData(
                    id: 123,
                    name: $device->name,
                    uniqueId: $device->unique_id,
                    model: $device->model,
                    phone: $device->phone,
                ));
        },
    );

    $job = new UpdateDeviceInTraccar($device);

    $job->handle($service);

    expect($device->fresh()?->last_sync_at)->not->toBeNull();
});

test('does nothing when the device is not synced with Traccar', function (): void {
    $device = Device::factory()->create([
        'traccar_device_id' => null,
        'last_sync_at' => null,
    ]);

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('update');
        },
    );

    $job = new UpdateDeviceInTraccar($device);

    $job->handle($service);

    expect($device->fresh()?->last_sync_at)->toBeNull();
});

test('does nothing when the device no longer exists', function (): void {
    $device = Device::factory()->create([
        'traccar_device_id' => 123,
    ]);

    $job = new UpdateDeviceInTraccar($device);

    $device->delete();

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('update');
        },
    );

    $job->handle($service);
});

test('rethrows Traccar exceptions so the queue can retry the job', function (): void {
    $device = Device::factory()->create([
        'traccar_device_id' => 123,
    ]);

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('update')
                ->once()
                ->andThrow(new RuntimeException('Traccar unavailable'));
        },
    );

    $job = new UpdateDeviceInTraccar($device);

    expect(
        fn () => $job->handle($service),
    )->toThrow(
        RuntimeException::class,
        'Traccar unavailable',
    );
});

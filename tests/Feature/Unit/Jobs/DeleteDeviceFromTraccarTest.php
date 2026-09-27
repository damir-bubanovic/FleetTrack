<?php

declare(strict_types=1);

use App\Jobs\DeleteDeviceFromTraccar;
use App\Models\Device;
use App\Services\Traccar\TraccarDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('deletes a device from Traccar', function (): void {
    $device = Device::factory()->create([
        'traccar_device_id' => 123,
    ]);

    $deviceId = $device->id;

    $device->delete();

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('delete')
                ->once()
                ->with(123);
        },
    );

    $job = new DeleteDeviceFromTraccar(
        deviceId: $deviceId,
        traccarDeviceId: 123,
    );

    $job->handle($service);
});

test('does nothing when the device still exists', function (): void {
    $device = Device::factory()->create([
        'traccar_device_id' => 123,
    ]);

    $service = $this->mock(
        TraccarDeviceService::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('delete');
        },
    );

    $job = new DeleteDeviceFromTraccar(
        deviceId: $device->id,
        traccarDeviceId: 123,
    );

    $job->handle($service);
});

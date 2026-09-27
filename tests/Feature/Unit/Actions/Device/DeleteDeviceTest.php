<?php

declare(strict_types=1);

use App\Actions\Device\DeleteDevice;
use App\Events\DeviceDeleted;
use App\Models\Device;
use Illuminate\Support\Facades\Event;

it('dispatches the deleted event after the device is deleted', function (): void {
    Event::listen(
        DeviceDeleted::class,
        function (DeviceDeleted $event): void {
            expect(
                Device::query()
                    ->whereKey($event->device->id)
                    ->exists(),
            )->toBeFalse();
        },
    );

    $device = Device::factory()->create();

    app(DeleteDevice::class)->handle($device);

    $this->assertDatabaseMissing('devices', [
        'id' => $device->id,
    ]);
});

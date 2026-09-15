<?php

declare(strict_types=1);

namespace App\Actions\Traccar;

use App\Data\Traccar\OverspeedEventData;
use App\Events\OverspeedOccurred;
use App\Models\Device;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;

final class HandleOverspeedEvent
{
    public function execute(OverspeedEventData $event): void
    {
        $device = Device::query()
            ->with('vehicle')
            ->where('traccar_device_id', $event->deviceId)
            ->first();

        if ($device === null) {
            throw (new ModelNotFoundException)->setModel(
                Device::class,
                [$event->deviceId],
            );
        }

        if ($device->vehicle === null) {
            throw new RuntimeException(
                "Device [{$device->id}] is not assigned to a vehicle."
            );
        }

        if ($device->vehicle->company_id !== $device->company_id) {
            throw new RuntimeException(
                'Device and assigned vehicle belong to different companies.'
            );
        }

        OverspeedOccurred::dispatch(
            device: $device,
            positionId: $event->positionId,
            speed: $event->speed,
            speedLimit: $event->speedLimit,
            traccarEventId: $event->eventId,
            occurredAt: $event->eventTime,
        );
    }
}

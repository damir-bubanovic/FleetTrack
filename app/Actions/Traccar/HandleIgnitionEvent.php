<?php

declare(strict_types=1);

namespace App\Actions\Traccar;

use App\Data\Traccar\IgnitionEventData;
use App\Events\IgnitionChanged;
use App\Models\Device;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;

final class HandleIgnitionEvent
{
    public function execute(IgnitionEventData $event): void
    {
        if (! in_array($event->type, ['ignitionOn', 'ignitionOff'], true)) {
            throw new RuntimeException(
                "Unsupported Traccar ignition event type [{$event->type}]."
            );
        }

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

        IgnitionChanged::dispatch(
            device: $device,
            type: $event->type,
            positionId: $event->positionId,
            traccarEventId: $event->eventId,
            occurredAt: $event->eventTime,
        );
    }
}

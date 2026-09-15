<?php

declare(strict_types=1);

namespace App\Actions\Alert;

use App\Events\DeviceWentOffline;
use App\Models\Alert;
use RuntimeException;

final class CreateDeviceOfflineAlert
{
    public function execute(DeviceWentOffline $event): Alert
    {
        $vehicle = $event->device->vehicle;

        if ($vehicle === null) {
            throw new RuntimeException(
                "Device [{$event->device->id}] is not assigned to a vehicle."
            );
        }

        return Alert::query()->firstOrCreate(
            [
                'company_id' => $event->device->company_id,
                'traccar_event_id' => $event->traccarEventId,
            ],
            [
                'vehicle_id' => $vehicle->id,
                'device_id' => $event->device->id,
                'geofence_id' => null,
                'type' => 'device_offline',
                'severity' => 'warning',
                'title' => 'Device went offline',
                'message' => sprintf(
                    '%s device %s went offline.',
                    $vehicle->registration_number,
                    $event->device->name,
                ),
                'occurred_at' => $event->occurredAt,
            ],
        );
    }
}

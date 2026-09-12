<?php

declare(strict_types=1);

namespace App\Actions\Traccar;

use App\Data\Traccar\GeofenceEventData;
use App\Events\GeofenceTransitionOccurred;
use App\Models\Device;
use App\Models\Geofence;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;

final class HandleGeofenceEvent
{
    /**
     * @return array{
     *     event: GeofenceEventData,
     *     device: Device,
     *     geofence: Geofence
     * }
     */
    public function execute(GeofenceEventData $event): array
    {
        if (! in_array($event->type, ['geofenceEnter', 'geofenceExit'], true)) {
            throw new RuntimeException(
                "Unsupported Traccar geofence event type [{$event->type}]."
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

        $geofence = Geofence::query()
            ->where('traccar_geofence_id', $event->geofenceId)
            ->first();

        if ($geofence === null) {
            throw (new ModelNotFoundException)->setModel(
                Geofence::class,
                [$event->geofenceId],
            );
        }

        if ($device->company_id !== $geofence->company_id) {
            throw new RuntimeException(
                'Traccar geofence event resolved to FleetTrack entities belonging to different companies.'
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

        GeofenceTransitionOccurred::dispatch(
            device: $device,
            geofence: $geofence,
            type: $event->type,
            traccarEventId: $event->eventId,
            occurredAt: $event->eventTime,
        );

        return [
            'event' => $event,
            'device' => $device,
            'geofence' => $geofence,
        ];
    }
}

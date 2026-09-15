<?php

declare(strict_types=1);

namespace App\Actions\Alert;

use App\Actions\AlertRule\ResolveAlertRule;
use App\Events\GeofenceTransitionOccurred;
use App\Models\Alert;
use RuntimeException;

final class CreateGeofenceAlert
{
    public function __construct(
        private readonly ResolveAlertRule $resolveAlertRule,
    ) {}

    public function execute(GeofenceTransitionOccurred $event): Alert
    {
        $vehicle = $event->device->vehicle;

        if ($vehicle === null) {
            throw new RuntimeException(
                "Device [{$event->device->id}] is not assigned to a vehicle."
            );
        }

        $type = match ($event->type) {
            'geofenceEnter' => 'geofence_enter',
            'geofenceExit' => 'geofence_exit',
            default => throw new RuntimeException(
                "Unsupported geofence transition type [{$event->type}]."
            ),
        };

        $title = match ($event->type) {
            'geofenceEnter' => 'Vehicle entered geofence',
            'geofenceExit' => 'Vehicle exited geofence',
        };

        $alertRule = $this->resolveAlertRule->execute(
            $vehicle,
            $type,
        );

        return Alert::query()->firstOrCreate(
            [
                'company_id' => $event->geofence->company_id,
                'traccar_event_id' => $event->traccarEventId,
            ],
            [
                'vehicle_id' => $vehicle->id,
                'device_id' => $event->device->id,
                'geofence_id' => $event->geofence->id,
                'type' => $type,
                'severity' => $alertRule !== null
                    ? $alertRule->severity
                    : 'info',
                'title' => $title,
                'message' => $this->message(
                    vehicleRegistration: $vehicle->registration_number,
                    geofenceName: $event->geofence->name,
                    type: $event->type,
                ),
                'occurred_at' => $event->occurredAt,
            ],
        );
    }

    private function message(
        string $vehicleRegistration,
        string $geofenceName,
        string $type,
    ): string {
        return match ($type) {
            'geofenceEnter' => "{$vehicleRegistration} entered {$geofenceName}.",
            'geofenceExit' => "{$vehicleRegistration} exited {$geofenceName}.",
            default => throw new RuntimeException(
                "Unsupported geofence transition type [{$type}]."
            ),
        };
    }
}

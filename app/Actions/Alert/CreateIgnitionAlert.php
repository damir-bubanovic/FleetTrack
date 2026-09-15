<?php

declare(strict_types=1);

namespace App\Actions\Alert;

use App\Actions\AlertRule\ResolveAlertRule;
use App\Events\IgnitionChanged;
use App\Models\Alert;
use RuntimeException;

final class CreateIgnitionAlert
{
    public function __construct(
        private readonly ResolveAlertRule $resolveAlertRule,
    ) {}

    public function execute(IgnitionChanged $event): Alert
    {
        $vehicle = $event->device->vehicle;

        if ($vehicle === null) {
            throw new RuntimeException(
                "Device [{$event->device->id}] is not assigned to a vehicle."
            );
        }

        [$type, $title, $message] = match ($event->type) {
            'ignitionOn' => [
                'ignition_on',
                'Vehicle ignition turned on',
                sprintf(
                    '%s ignition was turned on.',
                    $vehicle->registration_number,
                ),
            ],
            'ignitionOff' => [
                'ignition_off',
                'Vehicle ignition turned off',
                sprintf(
                    '%s ignition was turned off.',
                    $vehicle->registration_number,
                ),
            ],
            default => throw new RuntimeException(
                "Unsupported ignition event type [{$event->type}]."
            ),
        };

        $alertRule = $this->resolveAlertRule->execute(
            $vehicle,
            $type,
        );

        return Alert::query()->firstOrCreate(
            [
                'company_id' => $event->device->company_id,
                'traccar_event_id' => $event->traccarEventId,
            ],
            [
                'vehicle_id' => $vehicle->id,
                'device_id' => $event->device->id,
                'geofence_id' => null,
                'type' => $type,
                'severity' => $alertRule !== null
                    ? $alertRule->severity
                    : 'info',
                'title' => $title,
                'message' => $message,
                'occurred_at' => $event->occurredAt,
            ],
        );
    }
}

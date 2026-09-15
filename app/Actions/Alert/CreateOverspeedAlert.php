<?php

declare(strict_types=1);

namespace App\Actions\Alert;

use App\Actions\AlertRule\ResolveAlertRule;
use App\Events\OverspeedOccurred;
use App\Models\Alert;
use RuntimeException;

final class CreateOverspeedAlert
{
    private const KNOTS_TO_KMH = 1.852;

    public function __construct(
        private readonly ResolveAlertRule $resolveAlertRule,
    ) {}

    public function execute(OverspeedOccurred $event): Alert
    {
        $vehicle = $event->device->vehicle;

        if ($vehicle === null) {
            throw new RuntimeException(
                "Device [{$event->device->id}] is not assigned to a vehicle."
            );
        }

        $speedKmh = $this->toKilometresPerHour($event->speed);
        $speedLimitKmh = $this->toKilometresPerHour($event->speedLimit);

        $alertRule = $this->resolveAlertRule->execute(
            $vehicle,
            'overspeed',
            $speedKmh,
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
                'type' => 'overspeed',
                'severity' => $alertRule !== null
                    ? $alertRule->severity
                    : 'warning',
                'title' => 'Vehicle exceeded speed limit',
                'message' => sprintf(
                    '%s was travelling at %.0f km/h in a %.0f km/h zone.',
                    $vehicle->registration_number,
                    $speedKmh,
                    $speedLimitKmh,
                ),
                'occurred_at' => $event->occurredAt,
            ],
        );
    }

    private function toKilometresPerHour(float $knots): float
    {
        return $knots * self::KNOTS_TO_KMH;
    }
}

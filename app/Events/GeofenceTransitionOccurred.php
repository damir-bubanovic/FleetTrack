<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Device;
use App\Models\Geofence;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class GeofenceTransitionOccurred
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Device $device,
        public readonly Geofence $geofence,
        public readonly string $type,
        public readonly int $traccarEventId,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class VehicleAttachedToGeofence
{
    use Dispatchable;

    public function __construct(
        public Geofence $geofence,
        public Vehicle $vehicle,
    ) {}
}

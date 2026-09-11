<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Geofence;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GeofenceDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Geofence $geofence,
    ) {}
}

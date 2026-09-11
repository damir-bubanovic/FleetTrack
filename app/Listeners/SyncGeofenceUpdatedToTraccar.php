<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GeofenceUpdated;
use App\Jobs\UpdateGeofenceInTraccar;

class SyncGeofenceUpdatedToTraccar
{
    public function handle(GeofenceUpdated $event): void
    {
        UpdateGeofenceInTraccar::dispatch($event->geofence->id);
    }
}

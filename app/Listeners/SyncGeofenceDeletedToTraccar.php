<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GeofenceDeleted;
use App\Jobs\DeleteGeofenceFromTraccar;

class SyncGeofenceDeletedToTraccar
{
    public function handle(GeofenceDeleted $event): void
    {
        if ($event->geofence->traccar_geofence_id === null) {
            return;
        }

        DeleteGeofenceFromTraccar::dispatch(
            $event->geofence->id,
            $event->geofence->traccar_geofence_id,
        );
    }
}

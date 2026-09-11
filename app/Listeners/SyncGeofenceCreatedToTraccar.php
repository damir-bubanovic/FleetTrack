<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GeofenceCreated;
use App\Jobs\SyncGeofenceToTraccar;

class SyncGeofenceCreatedToTraccar
{
    public function handle(GeofenceCreated $event): void
    {
        SyncGeofenceToTraccar::dispatch($event->geofence->id);
    }
}

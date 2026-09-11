<?php

declare(strict_types=1);

namespace App\Actions\Geofence;

use App\Events\GeofenceDeleted;
use App\Models\Geofence;
use Illuminate\Support\Facades\DB;

class DeleteGeofence
{
    public function handle(Geofence $geofence): void
    {
        DB::transaction(function () use ($geofence): void {
            GeofenceDeleted::dispatch($geofence);

            $geofence->delete();
        });
    }
}

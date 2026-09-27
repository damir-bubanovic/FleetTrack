<?php

declare(strict_types=1);

namespace App\Actions\Geofence;

use App\Events\GeofenceDeleted;
use App\Models\Geofence;
use Illuminate\Support\Facades\DB;

final class DeleteGeofence
{
    public function handle(Geofence $geofence): void
    {
        DB::transaction(function () use ($geofence): void {
            $geofence->vehicles()->detach();

            $geofence->delete();
        });

        GeofenceDeleted::dispatch($geofence);
    }
}

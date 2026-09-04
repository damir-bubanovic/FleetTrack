<?php

declare(strict_types=1);

namespace App\Actions\Geofence;

use App\Models\Geofence;
use Illuminate\Support\Facades\DB;

class DeleteGeofence
{
    /**
     * Delete a geofence.
     */
    public function handle(Geofence $geofence): void
    {
        DB::transaction(function () use ($geofence): void {
            $geofence->delete();
        });
    }
}

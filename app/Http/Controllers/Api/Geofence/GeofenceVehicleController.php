<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Geofence;

use App\Actions\Geofence\AttachVehicleToGeofence;
use App\Actions\Geofence\DetachVehicleFromGeofence;
use App\Http\Controllers\Controller;
use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Http\Response;

class GeofenceVehicleController extends Controller
{
    public function store(
        Geofence $geofence,
        Vehicle $vehicle,
        AttachVehicleToGeofence $action,
    ): Response {
        $this->authorize('update', $geofence);

        $action->handle($geofence, $vehicle);

        return response()->noContent();
    }

    public function destroy(
        Geofence $geofence,
        Vehicle $vehicle,
        DetachVehicleFromGeofence $action,
    ): Response {
        $this->authorize('update', $geofence);

        $action->handle($geofence, $vehicle);

        return response()->noContent();
    }
}

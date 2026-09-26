<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Geofence;
use App\Models\Vehicle;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DetachGeofenceFromDeviceInTraccar implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $geofenceId,
        public readonly int $vehicleId,
        public readonly int $traccarDeviceId,
    ) {}

    public function handle(
        TraccarGeofenceService $service,
    ): void {
        $geofence = Geofence::find($this->geofenceId);
        $vehicle = Vehicle::find($this->vehicleId);

        if ($geofence === null || $vehicle === null) {
            return;
        }

        if ($geofence->vehicles()->whereKey($vehicle->id)->exists()) {
            return;
        }

        if ($geofence->traccar_geofence_id === null) {
            return;
        }

        try {
            $service->detachDevice(
                geofenceId: $geofence->traccar_geofence_id,
                deviceId: $this->traccarDeviceId,
            );

            Log::info('Geofence detached from Traccar device.', [
                'geofence_id' => $geofence->id,
                'vehicle_id' => $vehicle->id,
                'traccar_geofence_id' => $geofence->traccar_geofence_id,
                'traccar_device_id' => $this->traccarDeviceId,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to detach geofence from Traccar device.', [
                'geofence_id' => $geofence->id,
                'vehicle_id' => $vehicle->id,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}

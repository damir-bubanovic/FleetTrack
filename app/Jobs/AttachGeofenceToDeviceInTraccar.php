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

class AttachGeofenceToDeviceInTraccar implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $geofenceId,
        public readonly int $vehicleId,
    ) {}

    public function handle(
        TraccarGeofenceService $service,
    ): void {
        $geofence = Geofence::find($this->geofenceId);
        $vehicle = Vehicle::with('device')->find($this->vehicleId);

        if ($geofence === null || $vehicle === null) {
            return;
        }

        if (! $geofence->vehicles()->whereKey($vehicle->id)->exists()) {
            return;
        }

        if ($geofence->traccar_geofence_id === null) {
            return;
        }

        $device = $vehicle->device;

        if ($device === null || $device->traccar_device_id === null) {
            return;
        }

        try {
            $service->attachDevice(
                geofenceId: $geofence->traccar_geofence_id,
                deviceId: $device->traccar_device_id,
            );

            Log::info('Geofence attached to Traccar device.', [
                'geofence_id' => $geofence->id,
                'vehicle_id' => $vehicle->id,
                'device_id' => $device->id,
                'traccar_geofence_id' => $geofence->traccar_geofence_id,
                'traccar_device_id' => $device->traccar_device_id,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to attach geofence to Traccar device.', [
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

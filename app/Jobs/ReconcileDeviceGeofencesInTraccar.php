<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Device;
use App\Models\Vehicle;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class ReconcileDeviceGeofencesInTraccar implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $deviceId,
        public readonly ?int $previousVehicleId,
    ) {}

    public function handle(
        TraccarGeofenceService $traccarGeofenceService,
    ): void {
        $device = Device::query()->find($this->deviceId);

        if ($device === null || $device->traccar_device_id === null) {
            return;
        }

        if ($this->previousVehicleId === $device->vehicle_id) {
            return;
        }

        $previousGeofenceIds = $this->geofenceIdsForVehicle(
            $this->previousVehicleId,
        );

        $currentGeofenceIds = $this->geofenceIdsForVehicle(
            $device->vehicle_id,
        );

        $geofencesToDetach = $previousGeofenceIds->diff(
            $currentGeofenceIds,
        );

        $geofencesToAttach = $currentGeofenceIds->diff(
            $previousGeofenceIds,
        );

        foreach ($geofencesToDetach as $traccarGeofenceId) {
            $traccarGeofenceService->detachDevice(
                $traccarGeofenceId,
                $device->traccar_device_id,
            );
        }

        foreach ($geofencesToAttach as $traccarGeofenceId) {
            $traccarGeofenceService->attachDevice(
                $traccarGeofenceId,
                $device->traccar_device_id,
            );
        }
    }

    /**
     * @return Collection<int, int>
     */
    private function geofenceIdsForVehicle(
        ?int $vehicleId,
    ): Collection {
        if ($vehicleId === null) {
            return collect();
        }

        $vehicle = Vehicle::query()
            ->with('geofences')
            ->find($vehicleId);

        if ($vehicle === null) {
            return collect();
        }

        return $vehicle->geofences
            ->pluck('traccar_geofence_id')
            ->filter(
                static fn (?int $id): bool => $id !== null,
            )
            ->values();
    }
}

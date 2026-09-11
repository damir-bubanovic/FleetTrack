<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Geofence;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGeofenceToTraccar implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $geofenceId,
    ) {}

    public function handle(
        TraccarGeofenceService $traccarGeofenceService,
    ): void {
        $geofence = Geofence::query()->find($this->geofenceId);

        if ($geofence === null) {
            return;
        }

        if ($geofence->traccar_geofence_id !== null) {
            return;
        }

        $payload = [
            'name' => $geofence->name,
            'description' => $geofence->description,
            'area' => $geofence->area,
        ];

        $traccarGeofence = $traccarGeofenceService->create($payload);

        $geofence->update([
            'traccar_geofence_id' => $traccarGeofence->id,
            'last_sync_at' => now(),
        ]);

        Log::info('Geofence synchronized to Traccar.', [
            'geofence_id' => $geofence->id,
            'traccar_geofence_id' => $traccarGeofence->id,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to sync geofence to Traccar.', [
            'geofence_id' => $this->geofenceId,
            'exception' => $exception->getMessage(),
        ]);

        report($exception);
    }
}

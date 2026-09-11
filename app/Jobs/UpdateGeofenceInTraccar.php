<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Geofence;
use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateGeofenceInTraccar implements ShouldQueue
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

        if ($geofence->traccar_geofence_id === null) {
            SyncGeofenceToTraccar::dispatch($geofence->id);

            return;
        }

        $traccarGeofenceService->update(
            $geofence->traccar_geofence_id,
            [
                'name' => $geofence->name,
                'description' => $geofence->description,
                'area' => $geofence->area,
            ],
        );

        $geofence->update([
            'last_sync_at' => now(),
        ]);

        Log::info('Geofence updated in Traccar.', [
            'geofence_id' => $geofence->id,
            'traccar_geofence_id' => $geofence->traccar_geofence_id,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to update geofence in Traccar.', [
            'geofence_id' => $this->geofenceId,
            'exception' => $exception->getMessage(),
        ]);

        report($exception);
    }
}

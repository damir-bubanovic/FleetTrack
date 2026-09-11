<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Traccar\TraccarGeofenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteGeofenceFromTraccar implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $geofenceId,
        public readonly int $traccarGeofenceId,
    ) {}

    public function handle(
        TraccarGeofenceService $traccarGeofenceService,
    ): void {
        $traccarGeofenceService->delete(
            $this->traccarGeofenceId,
        );

        Log::info('Geofence deleted from Traccar.', [
            'geofence_id' => $this->geofenceId,
            'traccar_geofence_id' => $this->traccarGeofenceId,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to delete geofence from Traccar.', [
            'geofence_id' => $this->geofenceId,
            'traccar_geofence_id' => $this->traccarGeofenceId,
            'exception' => $exception->getMessage(),
        ]);

        report($exception);
    }
}

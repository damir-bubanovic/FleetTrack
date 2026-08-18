<?php

namespace App\Jobs;

use App\Services\Traccar\TraccarDeviceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteDeviceFromTraccar implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $deviceId,
        public readonly int $traccarDeviceId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        TraccarDeviceService $traccarDeviceService,
    ): void {
        $traccarDeviceService->delete(
            $this->traccarDeviceId,
        );

        Log::info('Device deleted from Traccar.', [
            'device_id' => $this->deviceId,
            'traccar_device_id' => $this->traccarDeviceId,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Failed to delete device from Traccar.', [
            'device_id' => $this->deviceId,
            'traccar_device_id' => $this->traccarDeviceId,
            'exception' => $exception->getMessage(),
        ]);

        report($exception);
    }
}

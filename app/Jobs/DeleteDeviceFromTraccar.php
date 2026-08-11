<?php

namespace App\Jobs;

use App\Models\Device;
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
        public readonly Device $device,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        TraccarDeviceService $traccarDeviceService,
    ): void {
        $traccarDeviceId = $this->device->traccar_device_id;

        if ($traccarDeviceId === null) {
            return;
        }

        $traccarDeviceService->delete($traccarDeviceId);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Failed to delete device from Traccar.', [
            'device_id' => $this->device->id,
            'traccar_device_id' => $this->device->traccar_device_id,
            'exception' => $exception->getMessage(),
        ]);

        report($exception);
    }
}
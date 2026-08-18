<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\Traccar\TraccarDeviceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateDeviceInTraccar implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly Device $device,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        TraccarDeviceService $traccarDeviceService,
    ): void {
        $device = $this->device->fresh();

        if ($device === null) {
            return;
        }

        if ($device->traccar_device_id === null) {
            return;
        }

        $payload = [
            'name' => $device->name,
            'uniqueId' => $device->unique_id,
            'model' => $device->model,
            'phone' => $device->phone,
        ];

        $traccarDeviceService->update(
            $device->traccar_device_id,
            $payload,
        );

        $device->update([
            'last_sync_at' => now(),
        ]);

        Log::info('Device updated in Traccar.', [
            'device_id' => $device->id,
            'traccar_device_id' => $device->traccar_device_id,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Failed to update device in Traccar.', [
            'device_id' => $this->device->id,
            'traccar_device_id' => $this->device->traccar_device_id,
            'exception' => $exception->getMessage(),
        ]);

        report($exception);
    }
}

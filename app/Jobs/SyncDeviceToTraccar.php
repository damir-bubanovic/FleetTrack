<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\Traccar\TraccarDeviceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncDeviceToTraccar implements ShouldQueue
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

        if ($device->traccar_device_id !== null) {
            return;
        }

        $payload = [
            'name' => $device->name,
            'uniqueId' => $device->unique_id,
            'model' => $device->model,
            'phone' => $device->phone,
        ];

        $traccarDevice = $traccarDeviceService->create($payload);

        $device->update([
            'traccar_device_id' => $traccarDevice->id,
            'last_sync_at' => now(),
        ]);

        Log::info('Device synchronized to Traccar.', [
            'device_id' => $device->id,
            'traccar_device_id' => $traccarDevice->id,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Failed to sync device to Traccar.', [
            'device_id' => $this->device->id,
            'exception' => $exception->getMessage(),
        ]);

        report($exception);
    }
}

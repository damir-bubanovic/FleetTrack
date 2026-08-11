<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\Traccar\TraccarDeviceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncDeviceToTraccar implements ShouldQueue
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
        if ($this->device->traccar_device_id !== null) {
            return;
        }

        $traccarDevice = $traccarDeviceService->create([
            'name' => $this->device->name,
            'uniqueId' => $this->device->unique_id,
            'model' => $this->device->model,
            'phone' => $this->device->phone,
        ]);

        $this->device->update([
            'traccar_device_id' => $traccarDevice->id,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
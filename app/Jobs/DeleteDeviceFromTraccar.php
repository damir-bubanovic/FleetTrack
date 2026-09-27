<?php

declare(strict_types=1);

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
        public readonly int $deviceId,
        public readonly int $traccarDeviceId,
    ) {}

    public function handle(
        TraccarDeviceService $traccarDeviceService,
    ): void {
        if (Device::query()->whereKey($this->deviceId)->exists()) {
            return;
        }

        $traccarDeviceService->delete(
            $this->traccarDeviceId,
        );

        Log::info('Device deleted from Traccar.', [
            'device_id' => $this->deviceId,
            'traccar_device_id' => $this->traccarDeviceId,
        ]);
    }

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

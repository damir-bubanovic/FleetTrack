<?php

namespace App\Actions\Tracking;

use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Traccar\PositionService;

class GetVehicleLivePosition
{
    public function __construct(
        private readonly PositionService $positionService,
    ) {}

    /**
     * @return array{
     *     device: Device,
     *     position: non-empty-array<string, mixed>
     * }|null
     */
    public function handle(User $user, Vehicle $vehicle): ?array
    {
        $device = Device::query()
            ->visibleTo($user)
            ->where('vehicle_id', $vehicle->id)
            ->whereNotNull('traccar_device_id')
            ->with('vehicle')
            ->first();

        if ($device === null) {
            return null;
        }

        $response = $this->positionService->all([
            'deviceId' => $device->traccar_device_id,
        ]);

        $response->throw();

        /** @var array<int, mixed> $positions */
        $positions = $response->json();

        $position = collect($positions)
            ->first(
                fn (mixed $position): bool => is_array($position)
                    && $position !== []
                    && isset($position['deviceId'])
                    && is_numeric($position['deviceId'])
                    && (int) $position['deviceId'] === $device->traccar_device_id
            );

        if (! is_array($position) || $position === []) {
            return null;
        }

        /** @var non-empty-array<string, mixed> $position */
        return [
            'device' => $device,
            'position' => $position,
        ];
    }
}

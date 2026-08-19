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

        /** @var array<int, non-empty-array<string, mixed>> $positions */
        $positions = $response->json();

        $position = collect($positions)
            ->first(
                fn (array $position): bool => ($position['deviceId'] ?? null)
                    === $device->traccar_device_id
            );

        if ($position === null) {
            return null;
        }

        return [
            'device' => $device,
            'position' => $position,
        ];
    }
}
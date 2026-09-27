<?php

namespace App\Actions\Tracking;

use App\Models\Device;
use App\Models\User;
use App\Services\Traccar\PositionService;

class GetLivePositions
{
    public function __construct(
        private readonly PositionService $positionService,
    ) {}

    /**
     * @return array<int, array{
     *     device: Device|null,
     *     position: non-empty-array<string, mixed>
     * }>
     */
    public function handle(
        User $user,
        ?int $fleetId = null,
        ?int $vehicleId = null,
    ): array {
        $response = $this->positionService->all();

        $response->throw();

        /** @var array<int, mixed> $positions */
        $positions = $response->json();

        $devices = Device::query()
            ->visibleTo($user)
            ->whereNotNull('traccar_device_id')
            ->when(
                $fleetId !== null,
                fn ($query) => $query->whereHas(
                    'vehicle',
                    fn ($query) => $query->where('fleet_id', $fleetId)
                )
            )
            ->when(
                $vehicleId !== null,
                fn ($query) => $query->where('vehicle_id', $vehicleId)
            )
            ->with('vehicle')
            ->get()
            ->keyBy('traccar_device_id');

        return collect($positions)
            ->filter(
                fn (mixed $position): bool => is_array($position)
                    && $position !== []
                    && isset($position['deviceId'])
                    && is_numeric($position['deviceId'])
                    && $devices->has((int) $position['deviceId'])
            )
            ->map(function (array $position) use ($devices): array {
                $device = $devices->get((int) $position['deviceId']);

                return [
                    'device' => $device,
                    'position' => $position,
                ];
            })
            ->values()
            ->all();
    }
}

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
    public function handle(User $user, ?int $fleetId = null): array
    {
        $response = $this->positionService->all();

        $response->throw();

        /** @var array<int, array<string, mixed>> $positions */
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
            ->with('vehicle')
            ->get()
            ->keyBy('traccar_device_id');

        return collect($positions)
            ->filter(
                fn (array $position): bool => isset($position['deviceId'])
                    && $devices->has($position['deviceId'])
            )
            ->map(function (array $position) use ($devices): array {
                $device = $devices->get($position['deviceId']);

                return [
                    'device' => $device,
                    'position' => $position,
                ];
            })
            ->values()
            ->all();
    }
}

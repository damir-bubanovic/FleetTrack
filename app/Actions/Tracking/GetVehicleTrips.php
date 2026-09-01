<?php

namespace App\Actions\Tracking;

use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Traccar\ReportService;
use Carbon\CarbonInterface;

class GetVehicleTrips
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(
        User $user,
        Vehicle $vehicle,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $device = Device::query()
            ->visibleTo($user)
            ->where('vehicle_id', $vehicle->id)
            ->whereNotNull('traccar_device_id')
            ->first();

        if ($device === null) {
            return [];
        }

        $response = $this->reportService->trips([
            'deviceId' => $device->traccar_device_id,
            'from' => $from->toISOString(),
            'to' => $to->toISOString(),
        ]);

        $response->throw();

        /** @var array<int, array<string, mixed>> $trips */
        $trips = $response->json();

        return $trips;
    }
}

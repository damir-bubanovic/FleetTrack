<?php

declare(strict_types=1);

namespace App\Actions\Tracking;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\Traccar\ReportService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final readonly class GetVehicleCombinedReport
{
    public function __construct(
        private ReportService $reportService,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(
        User $user,
        Vehicle $vehicle,
        CarbonInterface $from,
        CarbonInterface $to,
    ): Collection {
        if (
            ! $user->hasRole('SuperAdmin')
            && $user->company_id !== $vehicle->company_id
        ) {
            return collect();
        }

        $device = $vehicle->device;

        if ($device === null || $device->traccar_device_id === null) {
            return collect();
        }

        $response = $this->reportService->combined([
            'deviceId' => $device->traccar_device_id,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
        ]);

        $response->throw();

        /** @var array<int, array<string, mixed>> $report */
        $report = $response->json();

        return collect($report);
    }
}

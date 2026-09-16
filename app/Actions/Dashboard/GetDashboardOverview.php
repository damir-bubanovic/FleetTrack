<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Actions\Tracking\GetLivePositions;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Device;
use App\Models\User;
use App\Support\Tracking\VehicleOnlineStatus;

final readonly class GetDashboardOverview
{
    public function __construct(
        private GetLivePositions $getLivePositions,
    ) {}

    /**
     * @return array<string, int>
     */
    public function handle(User $user): array
    {
        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            $companies = Company::query()
                ->where(
                    'slug',
                    '!=',
                    config('fleettrack.system_company_slug'),
                )
                ->withCount([
                    'fleets',
                    'vehicles',
                    'devices',
                ])
                ->get();

            $overview = [
                'companies' => $companies->count(),
                'fleets' => (int) $companies->sum('fleets_count'),
                'vehicles' => (int) $companies->sum('vehicles_count'),
                'devices' => (int) $companies->sum('devices_count'),
            ];
        } elseif ($user->company_id === null) {
            $overview = [
                'companies' => 0,
                'fleets' => 0,
                'vehicles' => 0,
                'devices' => 0,
            ];
        } else {
            $company = Company::query()
                ->withCount([
                    'fleets',
                    'vehicles',
                    'devices',
                ])
                ->find($user->company_id);

            $overview = $company === null
                ? [
                    'companies' => 0,
                    'fleets' => 0,
                    'vehicles' => 0,
                    'devices' => 0,
                ]
                : [
                    'companies' => 1,
                    'fleets' => (int) $company->fleets_count,
                    'vehicles' => (int) $company->vehicles_count,
                    'devices' => (int) $company->devices_count,
                ];
        }

        $synchronizedDevices = Device::query()
            ->visibleTo($user)
            ->whereNotNull('traccar_device_id')
            ->count();

        $positions = $this->getLivePositions->handle($user);

        $onlineVehicles = collect($positions)
            ->filter(function (array $item): bool {
                $fixTime = isset($item['position']['fixTime'])
                    ? (string) $item['position']['fixTime']
                    : null;

                return VehicleOnlineStatus::isOnline($fixTime);
            })
            ->count();

        $onlineDevices = collect($positions)
            ->filter(function (array $item): bool {
                $fixTime = isset($item['position']['fixTime'])
                    ? (string) $item['position']['fixTime']
                    : null;

                return VehicleOnlineStatus::isOnline($fixTime);
            })
            ->count();

        return [
            ...$overview,
            'online_vehicles' => $onlineVehicles,
            'offline_vehicles' => max(
                0,
                $overview['vehicles'] - $onlineVehicles,
            ),
            'offline_devices' => max(
                0,
                $synchronizedDevices - $onlineDevices,
            ),
        ];
    }
}

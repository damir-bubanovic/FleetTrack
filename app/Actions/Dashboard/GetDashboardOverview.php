<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;

final readonly class GetDashboardOverview
{
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

            return [
                'companies' => $companies->count(),
                'fleets' => $companies->sum('fleets_count'),
                'vehicles' => $companies->sum('vehicles_count'),
                'devices' => $companies->sum('devices_count'),
            ];
        }

        if ($user->company_id === null) {
            return [
                'companies' => 0,
                'fleets' => 0,
                'vehicles' => 0,
                'devices' => 0,
            ];
        }

        $company = Company::query()
            ->withCount([
                'fleets',
                'vehicles',
                'devices',
            ])
            ->find($user->company_id);

        if ($company === null) {
            return [
                'companies' => 0,
                'fleets' => 0,
                'vehicles' => 0,
                'devices' => 0,
            ];
        }

        return [
            'companies' => 1,
            'fleets' => $company->fleets_count,
            'vehicles' => $company->vehicles_count,
            'devices' => $company->devices_count,
        ];
    }
}

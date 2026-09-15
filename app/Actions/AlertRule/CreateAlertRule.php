<?php

declare(strict_types=1);

namespace App\Actions\AlertRule;

use App\Enums\UserRole;
use App\Models\AlertRule;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use RuntimeException;

final class CreateAlertRule
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): AlertRule
    {
        $isSuperAdmin = $user->hasRole(
            UserRole::SuperAdmin->value,
        );

        if (! $isSuperAdmin) {
            if ($user->company_id === null) {
                throw new RuntimeException(
                    'User is not assigned to a company.'
                );
            }

            $data['company_id'] = $user->company_id;
        }

        /** @var Company $company */
        $company = Company::query()->findOrFail(
            $data['company_id'],
        );

        $data['company_id'] = $company->id;

        if (isset($data['vehicle_id'])) {
            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()
                ->whereKey($data['vehicle_id'])
                ->where('company_id', $company->id)
                ->firstOrFail();

            $data['vehicle_id'] = $vehicle->id;
        }

        $data['conditions'] ??= [];

        return AlertRule::query()->create($data);
    }
}

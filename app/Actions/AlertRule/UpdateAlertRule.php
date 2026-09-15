<?php

declare(strict_types=1);

namespace App\Actions\AlertRule;

use App\Enums\UserRole;
use App\Models\AlertRule;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;

final class UpdateAlertRule
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(
        User $user,
        AlertRule $alertRule,
        array $data,
    ): AlertRule {
        $isSuperAdmin = $user->hasRole(
            UserRole::SuperAdmin->value,
        );

        if (! $isSuperAdmin) {
            unset($data['company_id']);
        }

        $companyId = $alertRule->company_id;

        if (
            $isSuperAdmin
            && array_key_exists('company_id', $data)
        ) {
            /** @var Company $company */
            $company = Company::query()->findOrFail(
                $data['company_id'],
            );

            $companyId = $company->id;
            $data['company_id'] = $companyId;
        }

        if (
            array_key_exists('vehicle_id', $data)
            && $data['vehicle_id'] !== null
        ) {
            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()
                ->whereKey($data['vehicle_id'])
                ->where('company_id', $companyId)
                ->firstOrFail();

            $data['vehicle_id'] = $vehicle->id;
        }

        if (
            $isSuperAdmin
            && $companyId !== $alertRule->company_id
            && ! array_key_exists('vehicle_id', $data)
            && $alertRule->vehicle_id !== null
        ) {
            $data['vehicle_id'] = null;
        }

        $alertRule->update($data);

        return $alertRule->refresh();
    }
}

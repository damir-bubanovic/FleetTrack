<?php

declare(strict_types=1);

namespace App\Actions\Driver;

use App\Enums\UserRole;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateDriver
{
    /**
     * Update an existing driver.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(
        User $user,
        Driver $driver,
        array $data
    ): Driver {
        return DB::transaction(function () use ($user, $driver, $data): Driver {
            $isSuperAdmin = $user->hasRole(UserRole::SuperAdmin->value);

            /*
             * Company users cannot move drivers
             * to another company.
             */
            if (! $isSuperAdmin) {
                unset($data['company_id']);
            }

            $companyId = $isSuperAdmin
                ? ($data['company_id'] ?? $driver->company_id)
                : $driver->company_id;

            /*
             * The selected fleet must belong to the company
             * that will own the driver.
             */
            if (isset($data['fleet_id'])) {
                /** @var Fleet $fleet */
                $fleet = Fleet::query()
                    ->where('company_id', $companyId)
                    ->findOrFail($data['fleet_id']);

                $data['fleet_id'] = $fleet->id;
            }

            $data['company_id'] = $companyId;

            $driver->update($data);

            return $driver->refresh();
        });
    }
}

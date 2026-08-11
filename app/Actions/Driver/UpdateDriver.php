<?php

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
     * @param array<string, mixed> $data
     */
    public function handle(
        User $user,
        Driver $driver,
        array $data
    ): Driver {
        return DB::transaction(function () use ($user, $driver, $data): Driver {

            $isSuperAdmin = $user->hasRole(UserRole::SuperAdmin->value)
                && $user->company_id === null;

            /*
             * Company users cannot move drivers
             * to another company.
             */
            if (! $isSuperAdmin) {
                unset($data['company_id']);
            }

            /** @var Fleet $fleet */
            $fleet = Fleet::query()
                ->whereKey($data['fleet_id'])
                ->where('company_id', $driver->company_id)
                ->firstOrFail();

            $data['company_id'] = $fleet->company_id;

            $driver->update($data);

            return $driver->refresh();
        });
    }
}
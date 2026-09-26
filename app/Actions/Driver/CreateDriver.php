<?php

namespace App\Actions\Driver;

use App\Enums\UserRole;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateDriver
{
    /**
     * Create a new driver.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Driver
    {
        return DB::transaction(function () use ($user, $data): Driver {
            $isSuperAdmin = $user->hasRole(
                UserRole::SuperAdmin->value,
            );

            $fleetQuery = Fleet::query()
                ->whereKey($data['fleet_id']);

            if (! $isSuperAdmin) {
                $fleetQuery->where(
                    'company_id',
                    $user->company_id,
                );
            }

            /** @var Fleet $fleet */
            $fleet = $fleetQuery->firstOrFail();

            $data['company_id'] = $fleet->company_id;
            $data['is_active'] ??= true;

            return Driver::create($data);
        });
    }
}

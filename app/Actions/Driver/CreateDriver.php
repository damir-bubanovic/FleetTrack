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
     */
    public function handle(User $user, array $data): Driver
    {
        return DB::transaction(function () use ($user, $data): Driver {

            $isSuperAdmin = $user->role === UserRole::SuperAdmin
                && $user->company_id === null;

            if (! $isSuperAdmin) {
                $data['company_id'] = $user->company_id;
            }

            $fleet = Fleet::query()
                ->whereKey($data['fleet_id'])
                ->where('company_id', $data['company_id'])
                ->firstOrFail();

            $data['company_id'] = $fleet->company_id;

            $data['is_active'] ??= true;

            return Driver::create($data);
        });
    }
}

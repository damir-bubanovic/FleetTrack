<?php

namespace App\Actions\Fleet;

use App\Enums\UserRole;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateFleet
{
    /**
     * Update an existing fleet.
     */
    public function handle(User $user, Fleet $fleet, array $data): Fleet
    {
        return DB::transaction(function () use ($user, $fleet, $data): Fleet {

            if (! $user->hasRole(UserRole::SuperAdmin->value)) {
                unset($data['company_id']);
            }

            $fleet->update($data);

            return $fleet->refresh();
        });
    }
}
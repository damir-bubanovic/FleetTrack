<?php

namespace App\Actions\Fleet;

use App\Enums\UserRole;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateFleet
{
    /**
     * Create a new fleet.
     */
    public function handle(User $user, array $data): Fleet
    {
        return DB::transaction(function () use ($user, $data): Fleet {
            $isSuperAdmin = $user->role === UserRole::SuperAdmin
                && $user->company_id === null;

            if (! $isSuperAdmin) {
                $data['company_id'] = $user->company_id;
            }

            $data['is_active'] ??= true;
            $data['timezone'] ??= config('app.timezone');

            return Fleet::create($data);
        });
    }
}
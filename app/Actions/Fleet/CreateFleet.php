<?php

namespace App\Actions\Fleet;

use App\Enums\UserRole;
use App\Models\Fleet;
use App\Models\User;

class CreateFleet
{
    /**
     * Create a new fleet.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(
        User $user,
        array $attributes,
    ): Fleet {
        $isSuperAdmin = $user->hasRole(
            UserRole::SuperAdmin->value,
        );

        $companyId = $isSuperAdmin
            ? $attributes['company_id']
            : $user->company_id;

        unset($attributes['company_id']);

        return Fleet::create([
            ...$attributes,
            'company_id' => $companyId,
        ]);
    }
}

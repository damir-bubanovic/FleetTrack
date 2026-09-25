<?php

declare(strict_types=1);

namespace App\Actions\Vehicle;

use App\Enums\UserRole;
use App\Models\Fleet;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateVehicle
{
    /**
     * Update an existing vehicle.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws AuthorizationException
     */
    public function handle(
        User $user,
        Vehicle $vehicle,
        array $attributes,
    ): Vehicle {
        /** @var Fleet $fleet */
        $fleet = Fleet::query()->findOrFail($attributes['fleet_id']);

        $isSuperAdmin = $user->hasRole(UserRole::SuperAdmin->value);

        if (
            ! $isSuperAdmin
            && $fleet->company_id !== $user->company_id
        ) {
            throw new AuthorizationException(
                'You cannot assign a vehicle to another company.'
            );
        }

        return DB::transaction(function () use (
            $vehicle,
            $attributes,
            $fleet,
        ): Vehicle {
            $vehicle->update([
                ...$attributes,
                'company_id' => $fleet->company_id,
            ]);

            $vehicle->device()
                ->update([
                    'company_id' => $fleet->company_id,
                ]);

            return $vehicle->refresh();
        });
    }
}

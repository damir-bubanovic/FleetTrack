<?php

namespace App\Actions\Vehicle;

use App\Models\Fleet;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;

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
        $fleet = Fleet::query()->findOrFail($attributes['fleet_id']);

        if (
            $user->company_id !== null
            && $fleet->company_id !== $user->company_id
        ) {
            throw new AuthorizationException(
                'You cannot assign a vehicle to another company.'
            );
        }

        $vehicle->update([
            ...$attributes,
            'company_id' => $fleet->company_id,
        ]);

        return $vehicle->refresh();
    }
}

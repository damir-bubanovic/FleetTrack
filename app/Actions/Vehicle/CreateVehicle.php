<?php

namespace App\Actions\Vehicle;

use App\Models\Fleet;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;

class CreateVehicle
{
    /**
     * Create a new vehicle.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws AuthorizationException
     */
    public function handle(
        User $user,
        array $attributes,
    ): Vehicle {
        /** @var Fleet $fleet */
        $fleet = Fleet::query()->findOrFail($attributes['fleet_id']);

        if (
            $user->company_id !== null
            && $fleet->company_id !== $user->company_id
        ) {
            throw new AuthorizationException(
                'You cannot assign a vehicle to another company.'
            );
        }

        return Vehicle::create([
            ...$attributes,
            'company_id' => $fleet->company_id,
        ]);
    }
}

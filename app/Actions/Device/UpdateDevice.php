<?php

namespace App\Actions\Device;

use App\Enums\UserRole;
use App\Events\DeviceUpdated;
use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateDevice
{
    /**
     * Update an existing device.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws AuthorizationException
     */
    public function handle(
        User $user,
        Device $device,
        array $attributes,
    ): Device {
        $vehicle = null;

        if (! empty($attributes['vehicle_id'])) {
            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()->findOrFail($attributes['vehicle_id']);

            if (
                ! $user->hasRole(UserRole::SuperAdmin->value)
                && $vehicle->company_id !== $user->company_id
            ) {
                throw new AuthorizationException(
                    'You cannot assign a device to a vehicle from another company.'
                );
            }
        }

        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            $companyId = $vehicle !== null
                ? $vehicle->company_id
                : $attributes['company_id'];
        } else {
            $companyId = $user->company_id;
        }

        $device->update([
            ...$attributes,
            'company_id' => $companyId,
        ]);

        $device = $device->refresh();

        event(new DeviceUpdated($device));

        return $device;
    }
}

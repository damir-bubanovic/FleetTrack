<?php

namespace App\Actions\Device;

use App\Enums\UserRole;
use App\Events\DeviceCreated;
use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CreateDevice
{
    /**
     * Create a new device.
     *
     * @param array<string, mixed> $attributes
     *
     * @throws AuthorizationException
     */
    public function handle(
        User $user,
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

        $companyId = $user->hasRole(UserRole::SuperAdmin->value)
            ? ($vehicle !== null
                ? $vehicle->company_id
                : $attributes['company_id'])
            : $user->company_id;

        $device = DB::transaction(function () use ($attributes, $companyId): Device {
            return Device::create([
                ...$attributes,
                'company_id' => $companyId,
                'traccar_device_id' => null,
            ]);
        });

        event(new DeviceCreated($device));

        return $device;
    }
}
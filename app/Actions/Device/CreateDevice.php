<?php

namespace App\Actions\Device;

use App\Enums\UserRole;
use App\Models\Device;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Traccar\TraccarDeviceService;
use Illuminate\Auth\Access\AuthorizationException;

class CreateDevice
{
    public function __construct(
        private readonly TraccarDeviceService $traccarDeviceService,
    ) {
    }

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
            ? ($vehicle?->company_id ?? $attributes['company_id'])
            : $user->company_id;

        $traccarDevice = $this->traccarDeviceService->create([
            'name' => $attributes['name'],
            'uniqueId' => $attributes['unique_id'],
        ]);

        return Device::create([
            ...$attributes,
            'company_id' => $companyId,
            'traccar_device_id' => $traccarDevice->id,
        ]);
    }
}
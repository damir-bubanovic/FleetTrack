<?php

namespace App\Actions\Vehicle;

use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;

class DeleteVehicle
{
    /**
     * Delete a vehicle.
     */
    public function handle(Vehicle $vehicle): void
    {
        $hasDevice = Device::query()
            ->where('vehicle_id', $vehicle->id)
            ->exists();

        if ($hasDevice) {
            throw ValidationException::withMessages([
                'vehicle' => [
                    'The vehicle cannot be deleted while it has an assigned device.',
                ],
            ]);
        }

        $vehicle->delete();
    }
}

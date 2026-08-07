<?php

namespace App\Actions\Vehicle;

use App\Models\Vehicle;

class DeleteVehicle
{
    /**
     * Delete the specified vehicle.
     */
    public function handle(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }
}

<?php

namespace App\Actions\Fleet;

use App\Models\Driver;
use App\Models\Fleet;
use Illuminate\Validation\ValidationException;

class DeleteFleet
{
    /**
     * Delete a fleet.
     */
    public function handle(Fleet $fleet): void
    {
        $hasDrivers = Driver::query()
            ->where('fleet_id', $fleet->id)
            ->exists();

        if ($fleet->vehicles()->exists() || $hasDrivers) {
            throw ValidationException::withMessages([
                'fleet' => [
                    'The fleet cannot be deleted while it has assigned vehicles or drivers.',
                ],
            ]);
        }

        $fleet->delete();
    }
}

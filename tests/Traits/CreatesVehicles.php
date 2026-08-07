<?php

namespace Tests\Traits;

use App\Models\Company;
use App\Models\Fleet;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

trait CreatesVehicles
{
    protected function createVehicle(
        Company $company,
        Fleet $fleet,
        array $attributes = []
    ): Vehicle {
        return Vehicle::factory()->create(
            array_merge(
                [
                    'company_id' => $company->id,
                    'fleet_id' => $fleet->id,
                ],
                $attributes
            )
        );
    }

    protected function createVehicles(
        Company $company,
        Fleet $fleet,
        int $count = 3
    ): Collection {
        return Vehicle::factory()
            ->count($count)
            ->create([
                'company_id' => $company->id,
                'fleet_id' => $fleet->id,
            ]);
    }
}
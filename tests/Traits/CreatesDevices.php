<?php

namespace Tests\Traits;

use App\Models\Company;
use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

trait CreatesDevices
{
    protected function createDevice(
        Company $company,
        ?Vehicle $vehicle = null,
        array $attributes = []
    ): Device {
        return Device::factory()->create(
            array_merge(
                [
                    'company_id' => $company->id,
                    'vehicle_id' => $vehicle?->id,
                ],
                $attributes
            )
        );
    }

    protected function createDevices(
        Company $company,
        ?Vehicle $vehicle = null,
        int $count = 3
    ): Collection {
        return Device::factory()
            ->count($count)
            ->create([
                'company_id' => $company->id,
                'vehicle_id' => $vehicle?->id,
            ]);
    }
}
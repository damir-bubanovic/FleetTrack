<?php

namespace Tests\Traits;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Fleet;
use Illuminate\Database\Eloquent\Collection;

trait CreatesDrivers
{
    protected function createDriver(
        Company $company,
        Fleet $fleet,
        array $attributes = [],
    ): Driver {
        return Driver::factory()->create(array_merge([
            'company_id' => $company->id,
            'fleet_id' => $fleet->id,
        ], $attributes));
    }

    protected function createDrivers(
        Company $company,
        Fleet $fleet,
        int $count = 3,
    ): Collection {
        return Driver::factory()
            ->count($count)
            ->create([
                'company_id' => $company->id,
                'fleet_id' => $fleet->id,
            ]);
    }
}
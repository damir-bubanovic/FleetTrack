<?php

namespace Tests\Traits;

use App\Models\Company;
use App\Models\Fleet;
use Illuminate\Database\Eloquent\Collection;

trait CreatesFleets
{
    protected function createFleet(
        Company $company,
        array $attributes = []
    ): Fleet {
        return Fleet::factory()->create(
            array_merge(
                [
                    'company_id' => $company->id,
                ],
                $attributes
            )
        );
    }

    protected function createFleets(
        Company $company,
        int $count = 3
    ): Collection {
        return Fleet::factory()
            ->count($count)
            ->create([
                'company_id' => $company->id,
            ]);
    }
}
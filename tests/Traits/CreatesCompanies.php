<?php

namespace Tests\Traits;

use App\Models\Company;

trait CreatesCompanies
{
    protected function createCompany(array $attributes = []): Company
    {
        return Company::factory()->create($attributes);
    }

    protected function createCompanies(int $count = 3): \Illuminate\Database\Eloquent\Collection
    {
        return Company::factory()
            ->count($count)
            ->create();
    }
}
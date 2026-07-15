<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Seed the application's companies.
     */
    public function run(): void
    {
        Company::factory()->create([
            'name' => 'FleetTrack Logistics',
            'slug' => 'fleettrack-logistics',
        ]);

        Company::factory()
            ->count(3)
            ->create();
    }
}

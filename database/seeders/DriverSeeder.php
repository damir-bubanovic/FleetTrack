<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Fleet;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Seed the application's drivers.
     */
    public function run(): void
    {
        Company::query()
            ->where('slug', '!=', config('fleettrack.system_company_slug'))
            ->each(function (Company $company): void {
                $fleets = Fleet::query()
                    ->where('company_id', $company->id)
                    ->get();

                if ($fleets->isEmpty()) {
                    return;
                }

                foreach ($fleets as $fleet) {
                    Driver::factory()
                        ->count(5)
                        ->create([
                            'company_id' => $company->id,
                            'fleet_id' => $fleet->id,
                        ]);
                }
            });
    }
}
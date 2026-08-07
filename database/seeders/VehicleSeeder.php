<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Fleet;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::query()
            ->each(function (Company $company): void {

                $company->fleets()
                    ->each(function (Fleet $fleet) use ($company): void {

                        Vehicle::factory()
                            ->count(5)
                            ->create([
                                'company_id' => $company->id,
                                'fleet_id' => $fleet->id,
                            ]);
                    });
            });
    }
}

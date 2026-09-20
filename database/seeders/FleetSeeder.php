<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Fleet;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    /**
     * Seed the application's fleets.
     */
    public function run(): void
    {
        Company::query()
            ->where('slug', '!=', config('fleettrack.system_company_slug'))
            ->each(function (Company $company): void {
                $fleets = [
                    [
                        'code' => 'MAIN',
                        'name' => "{$company->name} Main Fleet",
                    ],
                    [
                        'code' => 'SECONDARY',
                        'name' => "{$company->name} Secondary Fleet",
                    ],
                ];

                foreach ($fleets as $fleet) {
                    Fleet::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'code' => $fleet['code'],
                        ],
                        [
                            'name' => $fleet['name'],
                            'email' => $company->email,
                            'phone' => $company->phone,
                            'address' => $company->address,
                            'timezone' => config('app.timezone'),
                            'is_active' => true,
                        ],
                    );
                }
            });
    }
}

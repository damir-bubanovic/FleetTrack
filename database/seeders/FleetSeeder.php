<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Fleet;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::query()
            ->each(function (Company $company): void {
                Fleet::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => 'MAIN',
                    ],
                    [
                        'name' => "{$company->name} Fleet",
                        'email' => $company->email,
                        'phone' => $company->phone,
                        'address' => $company->address,
                        'timezone' => config('app.timezone'),
                        'is_active' => true,
                    ]
                );
            });
    }
}

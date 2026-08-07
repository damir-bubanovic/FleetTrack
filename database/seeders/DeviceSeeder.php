<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(function (Company $company) {
            Device::factory()
                ->count(fake()->numberBetween(5, 15))
                ->create([
                    'company_id' => $company->id,
                ]);
        });
    }
}

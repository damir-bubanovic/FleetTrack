<?php

namespace Database\Seeders;

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
        Fleet::query()
            ->orderBy('id')
            ->each(function (Fleet $fleet): void {
                Driver::factory()
                    ->count(5)
                    ->create([
                        'company_id' => $fleet->company_id,
                        'fleet_id' => $fleet->id,
                    ]);
            });
    }
}

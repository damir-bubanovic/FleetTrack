<?php

namespace Database\Seeders;

use App\Models\Fleet;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Seed the application's vehicles.
     */
    public function run(): void
    {
        Fleet::query()
            ->orderBy('id')
            ->each(function (Fleet $fleet): void {
                Vehicle::factory()
                    ->count(5)
                    ->create([
                        'company_id' => $fleet->company_id,
                        'fleet_id' => $fleet->id,
                    ]);
            });
    }
}

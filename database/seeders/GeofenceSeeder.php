<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Geofence;
use Illuminate\Database\Seeder;

class GeofenceSeeder extends Seeder
{
    /**
     * Seed the application's geofences.
     */
    public function run(): void
    {
        Company::query()
            ->where('slug', '!=', config('fleettrack.system_company_slug'))
            ->with('vehicles')
            ->orderBy('id')
            ->each(function (Company $company): void {
                $geofences = collect([
                    Geofence::factory()->create([
                        'company_id' => $company->id,
                        'name' => "{$company->name} Headquarters",
                        'description' => 'Primary company operating area.',
                        'area' => 'CIRCLE (45.8150 15.9819, 500)',
                        'traccar_geofence_id' => null,
                        'last_sync_at' => null,
                    ]),
                    Geofence::factory()->create([
                        'company_id' => $company->id,
                        'name' => "{$company->name} Depot",
                        'description' => 'Fleet depot and vehicle parking area.',
                        'area' => 'CIRCLE (45.8030 15.9500, 350)',
                        'traccar_geofence_id' => null,
                        'last_sync_at' => null,
                    ]),
                    Geofence::factory()->create([
                        'company_id' => $company->id,
                        'name' => "{$company->name} Service Area",
                        'description' => 'Regular fleet service area.',
                        'area' => 'CIRCLE (45.8300 16.0200, 750)',
                        'traccar_geofence_id' => null,
                        'last_sync_at' => null,
                    ]),
                ]);

                $vehicles = $company->vehicles->values();

                $geofences->each(
                    function (Geofence $geofence, int $index) use ($vehicles): void {
                        $vehicleIds = $vehicles
                            ->slice($index * 3, 4)
                            ->pluck('id');

                        if ($vehicleIds->isEmpty()) {
                            return;
                        }

                        $geofence->vehicles()->attach($vehicleIds);
                    },
                );
            });
    }
}

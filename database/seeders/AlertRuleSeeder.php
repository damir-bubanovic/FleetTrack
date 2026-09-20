<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use App\Models\Company;
use Illuminate\Database\Seeder;

class AlertRuleSeeder extends Seeder
{
    /**
     * Seed the application's alert rules.
     */
    public function run(): void
    {
        Company::query()
            ->where('slug', '!=', config('fleettrack.system_company_slug'))
            ->with('vehicles')
            ->orderBy('id')
            ->each(function (Company $company): void {
                AlertRule::factory()->create([
                    'company_id' => $company->id,
                    'vehicle_id' => null,
                    'name' => 'Fleet overspeed warning',
                    'type' => 'overspeed',
                    'severity' => 'warning',
                    'conditions' => [
                        'speed_limit_kmh' => 90,
                    ],
                    'is_active' => true,
                ]);

                AlertRule::factory()->create([
                    'company_id' => $company->id,
                    'vehicle_id' => null,
                    'name' => 'Geofence entry notification',
                    'type' => 'geofence_enter',
                    'severity' => 'info',
                    'conditions' => [],
                    'is_active' => true,
                ]);

                AlertRule::factory()->create([
                    'company_id' => $company->id,
                    'vehicle_id' => null,
                    'name' => 'Geofence exit notification',
                    'type' => 'geofence_exit',
                    'severity' => 'info',
                    'conditions' => [],
                    'is_active' => true,
                ]);

                AlertRule::factory()->create([
                    'company_id' => $company->id,
                    'vehicle_id' => null,
                    'name' => 'Device offline warning',
                    'type' => 'device_offline',
                    'severity' => 'critical',
                    'conditions' => [],
                    'is_active' => true,
                ]);

                AlertRule::factory()->create([
                    'company_id' => $company->id,
                    'vehicle_id' => null,
                    'name' => 'Ignition on notification',
                    'type' => 'ignition_on',
                    'severity' => 'info',
                    'conditions' => [],
                    'is_active' => true,
                ]);

                AlertRule::factory()->create([
                    'company_id' => $company->id,
                    'vehicle_id' => null,
                    'name' => 'Ignition off notification',
                    'type' => 'ignition_off',
                    'severity' => 'info',
                    'conditions' => [],
                    'is_active' => true,
                ]);

                $vehicle = $company->vehicles->first();

                if ($vehicle !== null) {
                    AlertRule::factory()->create([
                        'company_id' => $company->id,
                        'vehicle_id' => $vehicle->id,
                        'name' => "{$vehicle->registration_number} strict speed limit",
                        'type' => 'overspeed',
                        'severity' => 'critical',
                        'conditions' => [
                            'speed_limit_kmh' => 70,
                        ],
                        'is_active' => true,
                    ]);
                }
            });
    }
}

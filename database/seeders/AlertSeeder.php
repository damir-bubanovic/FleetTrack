<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class AlertSeeder extends Seeder
{
    /**
     * Seed the application's alerts.
     */
    public function run(): void
    {
        Company::query()
            ->where('slug', '!=', config('fleettrack.system_company_slug'))
            ->with([
                'vehicles.device',
                'geofences',
            ])
            ->orderBy('id')
            ->each(function (Company $company): void {
                $vehicles = $company->vehicles->values();

                if ($vehicles->isEmpty()) {
                    return;
                }

                $acknowledgedBy = User::query()
                    ->where('company_id', $company->id)
                    ->orderBy('id')
                    ->first();

                $geofences = $company->geofences->values();

                $this->createOverspeedAlert(
                    $vehicles[0],
                    now()->subMinutes(15),
                );

                $this->createDeviceOfflineAlert(
                    $vehicles[1] ?? $vehicles[0],
                    now()->subHours(2),
                );

                if ($geofences->isNotEmpty()) {
                    $this->createGeofenceAlert(
                        vehicle: $vehicles[2] ?? $vehicles[0],
                        geofenceId: $geofences[0]->id,
                        type: 'geofence_enter',
                        occurredAt: now()->subHours(5),
                    );

                    $this->createGeofenceAlert(
                        vehicle: $vehicles[3] ?? $vehicles[0],
                        geofenceId: $geofences[1]->id ?? $geofences[0]->id,
                        type: 'geofence_exit',
                        occurredAt: now()->subDay(),
                        acknowledgedBy: $acknowledgedBy,
                    );
                }

                $this->createIgnitionAlert(
                    vehicle: $vehicles[4] ?? $vehicles[0],
                    type: 'ignition_on',
                    occurredAt: now()->subDays(2),
                    acknowledgedBy: $acknowledgedBy,
                );

                $this->createIgnitionAlert(
                    vehicle: $vehicles[5] ?? $vehicles[0],
                    type: 'ignition_off',
                    occurredAt: now()->subDays(3),
                    acknowledgedBy: $acknowledgedBy,
                );
            });
    }

    private function createOverspeedAlert(
        Vehicle $vehicle,
        mixed $occurredAt,
    ): void {
        Alert::factory()->create([
            'company_id' => $vehicle->company_id,
            'vehicle_id' => $vehicle->id,
            'device_id' => $vehicle->device?->id,
            'geofence_id' => null,
            'type' => 'overspeed',
            'severity' => 'warning',
            'title' => 'Vehicle exceeded speed limit',
            'message' => sprintf(
                '%s was travelling at 108 km/h in a 90 km/h zone.',
                $vehicle->registration_number,
            ),
            'traccar_event_id' => null,
            'occurred_at' => $occurredAt,
            'acknowledged_at' => null,
            'acknowledged_by' => null,
        ]);
    }

    private function createDeviceOfflineAlert(
        Vehicle $vehicle,
        mixed $occurredAt,
    ): void {
        Alert::factory()->create([
            'company_id' => $vehicle->company_id,
            'vehicle_id' => $vehicle->id,
            'device_id' => $vehicle->device?->id,
            'geofence_id' => null,
            'type' => 'device_offline',
            'severity' => 'critical',
            'title' => 'Device went offline',
            'message' => sprintf(
                '%s tracking device went offline.',
                $vehicle->registration_number,
            ),
            'traccar_event_id' => null,
            'occurred_at' => $occurredAt,
            'acknowledged_at' => null,
            'acknowledged_by' => null,
        ]);
    }

    private function createGeofenceAlert(
        Vehicle $vehicle,
        int $geofenceId,
        string $type,
        mixed $occurredAt,
        ?User $acknowledgedBy = null,
    ): void {
        $entering = $type === 'geofence_enter';

        Alert::factory()->create([
            'company_id' => $vehicle->company_id,
            'vehicle_id' => $vehicle->id,
            'device_id' => $vehicle->device?->id,
            'geofence_id' => $geofenceId,
            'type' => $type,
            'severity' => 'info',
            'title' => $entering
                ? 'Vehicle entered geofence'
                : 'Vehicle exited geofence',
            'message' => $entering
                ? "{$vehicle->registration_number} entered a geofence."
                : "{$vehicle->registration_number} exited a geofence.",
            'traccar_event_id' => null,
            'occurred_at' => $occurredAt,
            'acknowledged_at' => $acknowledgedBy !== null
                ? $occurredAt
                : null,
            'acknowledged_by' => $acknowledgedBy?->id,
        ]);
    }

    private function createIgnitionAlert(
        Vehicle $vehicle,
        string $type,
        mixed $occurredAt,
        ?User $acknowledgedBy = null,
    ): void {
        $ignitionOn = $type === 'ignition_on';

        Alert::factory()->create([
            'company_id' => $vehicle->company_id,
            'vehicle_id' => $vehicle->id,
            'device_id' => $vehicle->device?->id,
            'geofence_id' => null,
            'type' => $type,
            'severity' => 'info',
            'title' => $ignitionOn
                ? 'Vehicle ignition turned on'
                : 'Vehicle ignition turned off',
            'message' => sprintf(
                '%s ignition was turned %s.',
                $vehicle->registration_number,
                $ignitionOn ? 'on' : 'off',
            ),
            'traccar_event_id' => null,
            'occurred_at' => $occurredAt,
            'acknowledged_at' => $acknowledgedBy !== null
                ? $occurredAt
                : null,
            'acknowledged_by' => $acknowledgedBy?->id,
        ]);
    }
}

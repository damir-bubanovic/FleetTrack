<?php

declare(strict_types=1);

use App\Events\GeofenceTransitionOccurred;
use App\Listeners\HandleGeofenceTransition;
use App\Models\Company;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an alert when handling a geofence transition', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
    ]);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
        'traccar_geofence_id' => 654321,
    ]);

    $event = new GeofenceTransitionOccurred(
        device: $device,
        geofence: $geofence,
        type: 'geofenceEnter',
        traccarEventId: 987654,
        occurredAt: new DateTimeImmutable('2026-09-12T10:30:00Z'),
    );

    app(HandleGeofenceTransition::class)->handle($event);

    $this->assertDatabaseHas('alerts', [
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'geofence_id' => $geofence->id,
        'type' => 'geofence_enter',
        'severity' => 'info',
        'title' => 'Vehicle entered geofence',
        'traccar_event_id' => 987654,
        'acknowledged_at' => null,
        'acknowledged_by' => null,
    ]);
});

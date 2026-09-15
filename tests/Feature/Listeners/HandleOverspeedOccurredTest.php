<?php

declare(strict_types=1);

use App\Actions\Alert\CreateOverspeedAlert;
use App\Events\OverspeedOccurred;
use App\Listeners\HandleOverspeedOccurred;
use App\Models\Alert;
use App\Models\Company;
use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an alert when an overspeed event is handled', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
    ]);

    $event = new OverspeedOccurred(
        device: $device->load('vehicle'),
        positionId: 456789,
        speed: 54.0,
        speedLimit: 43.2,
        traccarEventId: 987654,
        occurredAt: new DateTimeImmutable(
            '2026-09-15T08:30:00Z',
        ),
    );

    $listener = new HandleOverspeedOccurred(
        app(CreateOverspeedAlert::class),
    );

    $listener->handle($event);

    $alert = Alert::query()->sole();

    expect($alert->company_id)
        ->toBe($company->id)
        ->and($alert->vehicle_id)
        ->toBe($vehicle->id)
        ->and($alert->device_id)
        ->toBe($device->id)
        ->and($alert->geofence_id)
        ->toBeNull()
        ->and($alert->type)
        ->toBe('overspeed')
        ->and($alert->severity)
        ->toBe('warning')
        ->and($alert->traccar_event_id)
        ->toBe(987654)
        ->and($alert->message)
        ->toBe(
            "{$vehicle->registration_number} was travelling at 100 km/h in a 80 km/h zone."
        );
});

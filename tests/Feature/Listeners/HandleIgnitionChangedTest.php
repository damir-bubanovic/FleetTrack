<?php

declare(strict_types=1);

use App\Actions\Alert\CreateIgnitionAlert;
use App\Events\IgnitionChanged;
use App\Listeners\HandleIgnitionChanged;
use App\Models\Alert;
use App\Models\Company;
use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an alert when an ignition event is handled', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
    ]);

    $event = new IgnitionChanged(
        device: $device->load('vehicle'),
        type: 'ignitionOn',
        positionId: 456789,
        traccarEventId: 987654,
        occurredAt: new DateTimeImmutable(
            '2026-09-15T09:30:00Z',
        ),
    );

    $listener = new HandleIgnitionChanged(
        app(CreateIgnitionAlert::class),
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
        ->toBe('ignition_on')
        ->and($alert->severity)
        ->toBe('info')
        ->and($alert->title)
        ->toBe('Vehicle ignition turned on')
        ->and($alert->traccar_event_id)
        ->toBe(987654)
        ->and($alert->message)
        ->toBe(
            "{$vehicle->registration_number} ignition was turned on."
        );
});

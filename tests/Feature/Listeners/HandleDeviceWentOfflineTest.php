<?php

declare(strict_types=1);

use App\Actions\Alert\CreateDeviceOfflineAlert;
use App\Events\DeviceWentOffline;
use App\Listeners\HandleDeviceWentOffline;
use App\Models\Alert;
use App\Models\Company;
use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an alert when a device offline event is handled', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
        'registration_number' => 'ZG-1234-AB',
    ]);

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
        'name' => 'Tracker 01',
    ]);

    $event = new DeviceWentOffline(
        device: $device->load('vehicle'),
        traccarEventId: 987654,
        occurredAt: new DateTimeImmutable(
            '2026-09-15T10:30:00Z',
        ),
    );

    $listener = new HandleDeviceWentOffline(
        app(CreateDeviceOfflineAlert::class),
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
        ->toBe('device_offline')
        ->and($alert->severity)
        ->toBe('warning')
        ->and($alert->title)
        ->toBe('Device went offline')
        ->and($alert->traccar_event_id)
        ->toBe(987654)
        ->and($alert->message)
        ->toBe(
            'ZG-1234-AB device Tracker 01 went offline.'
        );

    expect($alert->occurred_at->toAtomString())
        ->toBe('2026-09-15T10:30:00+00:00');
});

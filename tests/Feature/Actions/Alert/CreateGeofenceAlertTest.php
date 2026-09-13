<?php

declare(strict_types=1);

use App\Actions\Alert\CreateGeofenceAlert;
use App\Events\GeofenceTransitionOccurred;
use App\Models\Alert;
use App\Models\Company;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createGeofenceTransitionFixtures(): array
{
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

    return [
        'company' => $company,
        'vehicle' => $vehicle,
        'device' => $device,
        'geofence' => $geofence,
    ];
}

function createGeofenceTransitionEvent(
    Device $device,
    Geofence $geofence,
    string $type = 'geofenceEnter',
    int $traccarEventId = 987654,
): GeofenceTransitionOccurred {
    return new GeofenceTransitionOccurred(
        device: $device,
        geofence: $geofence,
        type: $type,
        traccarEventId: $traccarEventId,
        occurredAt: new DateTimeImmutable('2026-09-12T10:30:00Z'),
    );
}

it('creates an alert for a geofence enter event', function (): void {
    $fixtures = createGeofenceTransitionFixtures();

    $event = createGeofenceTransitionEvent(
        device: $fixtures['device'],
        geofence: $fixtures['geofence'],
    );

    $alert = app(CreateGeofenceAlert::class)->execute($event);

    expect($alert)
        ->toBeInstanceOf(Alert::class)
        ->and($alert->company_id)->toBe($fixtures['company']->id)
        ->and($alert->vehicle_id)->toBe($fixtures['vehicle']->id)
        ->and($alert->device_id)->toBe($fixtures['device']->id)
        ->and($alert->geofence_id)->toBe($fixtures['geofence']->id)
        ->and($alert->type)->toBe('geofence_enter')
        ->and($alert->severity)->toBe('info')
        ->and($alert->title)->toBe('Vehicle entered geofence')
        ->and($alert->traccar_event_id)->toBe(987654)
        ->and($alert->acknowledged_at)->toBeNull()
        ->and($alert->acknowledged_by)->toBeNull();

    expect($alert->occurred_at->toAtomString())
        ->toBe('2026-09-12T10:30:00+00:00');

    expect($alert->message)
        ->toBe(
            "{$fixtures['vehicle']->registration_number} entered {$fixtures['geofence']->name}."
        );
});

it('creates an alert for a geofence exit event', function (): void {
    $fixtures = createGeofenceTransitionFixtures();

    $event = createGeofenceTransitionEvent(
        device: $fixtures['device'],
        geofence: $fixtures['geofence'],
        type: 'geofenceExit',
    );

    $alert = app(CreateGeofenceAlert::class)->execute($event);

    expect($alert->type)
        ->toBe('geofence_exit')
        ->and($alert->severity)->toBe('info')
        ->and($alert->title)->toBe('Vehicle exited geofence')
        ->and($alert->message)->toBe(
            "{$fixtures['vehicle']->registration_number} exited {$fixtures['geofence']->name}."
        );
});

it('does not create a duplicate alert for the same traccar event', function (): void {
    $fixtures = createGeofenceTransitionFixtures();

    $event = createGeofenceTransitionEvent(
        device: $fixtures['device'],
        geofence: $fixtures['geofence'],
    );

    $action = app(CreateGeofenceAlert::class);

    $firstAlert = $action->execute($event);
    $secondAlert = $action->execute($event);

    expect($secondAlert->id)
        ->toBe($firstAlert->id)
        ->and(Alert::query()->count())
        ->toBe(1);
});

it('allows different traccar events to create separate alerts', function (): void {
    $fixtures = createGeofenceTransitionFixtures();

    $firstEvent = createGeofenceTransitionEvent(
        device: $fixtures['device'],
        geofence: $fixtures['geofence'],
        type: 'geofenceEnter',
        traccarEventId: 1001,
    );

    $secondEvent = createGeofenceTransitionEvent(
        device: $fixtures['device'],
        geofence: $fixtures['geofence'],
        type: 'geofenceExit',
        traccarEventId: 1002,
    );

    $action = app(CreateGeofenceAlert::class);

    $action->execute($firstEvent);
    $action->execute($secondEvent);

    expect(Alert::query()->count())->toBe(2);

    $this->assertDatabaseHas('alerts', [
        'company_id' => $fixtures['company']->id,
        'traccar_event_id' => 1001,
        'type' => 'geofence_enter',
    ]);

    $this->assertDatabaseHas('alerts', [
        'company_id' => $fixtures['company']->id,
        'traccar_event_id' => 1002,
        'type' => 'geofence_exit',
    ]);
});

it('rejects an unsupported geofence transition type', function (): void {
    $fixtures = createGeofenceTransitionFixtures();

    $event = createGeofenceTransitionEvent(
        device: $fixtures['device'],
        geofence: $fixtures['geofence'],
        type: 'deviceOnline',
    );

    expect(
        fn () => app(CreateGeofenceAlert::class)->execute($event),
    )->toThrow(
        RuntimeException::class,
        'Unsupported geofence transition type [deviceOnline].',
    );

    expect(Alert::query()->count())->toBe(0);
});

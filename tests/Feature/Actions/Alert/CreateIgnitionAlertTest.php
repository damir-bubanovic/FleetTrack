<?php

declare(strict_types=1);

use App\Actions\Alert\CreateIgnitionAlert;
use App\Events\IgnitionChanged;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Company;
use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createIgnitionFixtures(): array
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

    return [
        'company' => $company,
        'vehicle' => $vehicle,
        'device' => $device,
    ];
}

function createIgnitionEvent(
    Device $device,
    string $type = 'ignitionOn',
    int $traccarEventId = 900001,
): IgnitionChanged {
    return new IgnitionChanged(
        device: $device,
        type: $type,
        positionId: 111222333,
        traccarEventId: $traccarEventId,
        occurredAt: new DateTimeImmutable(
            '2026-09-15T09:30:00Z',
        ),
    );
}

it('creates an ignition on alert', function (): void {
    $fixtures = createIgnitionFixtures();

    $alert = app(CreateIgnitionAlert::class)->execute(
        createIgnitionEvent($fixtures['device']),
    );

    expect($alert)
        ->toBeInstanceOf(Alert::class)
        ->and($alert->company_id)->toBe($fixtures['company']->id)
        ->and($alert->vehicle_id)->toBe($fixtures['vehicle']->id)
        ->and($alert->device_id)->toBe($fixtures['device']->id)
        ->and($alert->geofence_id)->toBeNull()
        ->and($alert->type)->toBe('ignition_on')
        ->and($alert->severity)->toBe('info')
        ->and($alert->title)->toBe('Vehicle ignition turned on')
        ->and($alert->message)->toBe(
            "{$fixtures['vehicle']->registration_number} ignition was turned on."
        )
        ->and($alert->traccar_event_id)->toBe(900001)
        ->and($alert->acknowledged_at)->toBeNull()
        ->and($alert->acknowledged_by)->toBeNull();

    expect($alert->occurred_at->toAtomString())
        ->toBe('2026-09-15T09:30:00+00:00');
});

it('creates an ignition off alert', function (): void {
    $fixtures = createIgnitionFixtures();

    $alert = app(CreateIgnitionAlert::class)->execute(
        createIgnitionEvent(
            device: $fixtures['device'],
            type: 'ignitionOff',
            traccarEventId: 900002,
        ),
    );

    expect($alert->type)
        ->toBe('ignition_off')
        ->and($alert->severity)
        ->toBe('info')
        ->and($alert->title)
        ->toBe('Vehicle ignition turned off')
        ->and($alert->message)
        ->toBe(
            "{$fixtures['vehicle']->registration_number} ignition was turned off."
        );
});

it('does not create a duplicate ignition alert for the same traccar event', function (): void {
    $fixtures = createIgnitionFixtures();

    $event = createIgnitionEvent($fixtures['device']);

    $action = app(CreateIgnitionAlert::class);

    $firstAlert = $action->execute($event);
    $secondAlert = $action->execute($event);

    expect($secondAlert->id)
        ->toBe($firstAlert->id)
        ->and(Alert::query()->count())
        ->toBe(1);
});

it('creates separate alerts for different ignition events', function (): void {
    $fixtures = createIgnitionFixtures();

    $action = app(CreateIgnitionAlert::class);

    $action->execute(
        createIgnitionEvent(
            device: $fixtures['device'],
            type: 'ignitionOn',
            traccarEventId: 1001,
        ),
    );

    $action->execute(
        createIgnitionEvent(
            device: $fixtures['device'],
            type: 'ignitionOff',
            traccarEventId: 1002,
        ),
    );

    expect(Alert::query()->count())->toBe(2);

    $this->assertDatabaseHas('alerts', [
        'company_id' => $fixtures['company']->id,
        'traccar_event_id' => 1001,
        'type' => 'ignition_on',
    ]);

    $this->assertDatabaseHas('alerts', [
        'company_id' => $fixtures['company']->id,
        'traccar_event_id' => 1002,
        'type' => 'ignition_off',
    ]);
});

it('rejects an unsupported ignition event type', function (): void {
    $fixtures = createIgnitionFixtures();

    $event = createIgnitionEvent(
        device: $fixtures['device'],
        type: 'unknownIgnition',
    );

    expect(
        fn () => app(CreateIgnitionAlert::class)->execute($event),
    )->toThrow(
        RuntimeException::class,
        'Unsupported ignition event type [unknownIgnition].',
    );

    expect(Alert::query()->count())->toBe(0);
});

it('rejects an ignition event when the device has no vehicle', function (): void {
    $company = Company::factory()->create();

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'traccar_device_id' => 123456,
    ]);

    $event = createIgnitionEvent($device);

    expect(
        fn () => app(CreateIgnitionAlert::class)->execute($event),
    )->toThrow(
        RuntimeException::class,
        "Device [{$device->id}] is not assigned to a vehicle.",
    );

    expect(Alert::query()->count())->toBe(0);
});

it('uses severity from a matching ignition on alert rule', function (): void {
    $fixtures = createIgnitionFixtures();

    AlertRule::factory()->create([
        'company_id' => $fixtures['company']->id,
        'vehicle_id' => null,
        'type' => 'ignition_on',
        'severity' => 'critical',
        'conditions' => [],
        'is_active' => true,
    ]);

    $alert = app(CreateIgnitionAlert::class)->execute(
        createIgnitionEvent(
            device: $fixtures['device'],
            type: 'ignitionOn',
            traccarEventId: 700003,
        ),
    );

    expect($alert->severity)->toBe('critical');
});

it('uses severity from a matching ignition off alert rule', function (): void {
    $fixtures = createIgnitionFixtures();

    AlertRule::factory()->create([
        'company_id' => $fixtures['company']->id,
        'vehicle_id' => null,
        'type' => 'ignition_off',
        'severity' => 'warning',
        'conditions' => [],
        'is_active' => true,
    ]);

    $alert = app(CreateIgnitionAlert::class)->execute(
        createIgnitionEvent(
            device: $fixtures['device'],
            type: 'ignitionOff',
            traccarEventId: 700004,
        ),
    );

    expect($alert->severity)->toBe('warning');
});

it('keeps the default ignition severity when no custom alert rule matches', function (): void {
    $fixtures = createIgnitionFixtures();

    $alert = app(CreateIgnitionAlert::class)->execute(
        createIgnitionEvent(
            device: $fixtures['device'],
            type: 'ignitionOn',
            traccarEventId: 700005,
        ),
    );

    expect($alert->severity)->toBe('info');
});

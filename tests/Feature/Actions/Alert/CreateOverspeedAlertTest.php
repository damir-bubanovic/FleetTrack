<?php

declare(strict_types=1);

use App\Actions\Alert\CreateOverspeedAlert;
use App\Events\OverspeedOccurred;
use App\Models\Alert;
use App\Models\Company;
use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createOverspeedFixtures(): array
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

function createOverspeedEvent(
    Device $device,
    float $speed = 54.0,
    float $speedLimit = 43.2,
    int $traccarEventId = 900001,
): OverspeedOccurred {
    return new OverspeedOccurred(
        device: $device,
        positionId: 111222333,
        speed: $speed,
        speedLimit: $speedLimit,
        traccarEventId: $traccarEventId,
        occurredAt: new DateTimeImmutable('2026-09-14T14:45:00Z'),
    );
}

it('creates an overspeed alert', function (): void {
    $fixtures = createOverspeedFixtures();

    $event = createOverspeedEvent($fixtures['device']);

    $alert = app(CreateOverspeedAlert::class)->execute($event);

    expect($alert)
        ->toBeInstanceOf(Alert::class)
        ->and($alert->company_id)->toBe($fixtures['company']->id)
        ->and($alert->vehicle_id)->toBe($fixtures['vehicle']->id)
        ->and($alert->device_id)->toBe($fixtures['device']->id)
        ->and($alert->geofence_id)->toBeNull()
        ->and($alert->type)->toBe('overspeed')
        ->and($alert->severity)->toBe('warning')
        ->and($alert->title)->toBe('Vehicle exceeded speed limit')
        ->and($alert->traccar_event_id)->toBe(900001)
        ->and($alert->acknowledged_at)->toBeNull()
        ->and($alert->acknowledged_by)->toBeNull();

    expect($alert->occurred_at->toAtomString())
        ->toBe('2026-09-14T14:45:00+00:00');

    expect($alert->message)
        ->toBe(
            "{$fixtures['vehicle']->registration_number} was travelling at 100 km/h in a 80 km/h zone."
        );
});

it('does not create a duplicate overspeed alert for the same traccar event', function (): void {
    $fixtures = createOverspeedFixtures();

    $event = createOverspeedEvent($fixtures['device']);

    $action = app(CreateOverspeedAlert::class);

    $firstAlert = $action->execute($event);
    $secondAlert = $action->execute($event);

    expect($secondAlert->id)
        ->toBe($firstAlert->id)
        ->and(Alert::query()->count())
        ->toBe(1);
});

it('creates separate alerts for different traccar overspeed events', function (): void {
    $fixtures = createOverspeedFixtures();

    $firstEvent = createOverspeedEvent(
        device: $fixtures['device'],
        traccarEventId: 1001,
    );

    $secondEvent = createOverspeedEvent(
        device: $fixtures['device'],
        traccarEventId: 1002,
    );

    $action = app(CreateOverspeedAlert::class);

    $action->execute($firstEvent);
    $action->execute($secondEvent);

    expect(Alert::query()->count())->toBe(2);

    $this->assertDatabaseHas('alerts', [
        'company_id' => $fixtures['company']->id,
        'traccar_event_id' => 1001,
        'type' => 'overspeed',
    ]);

    $this->assertDatabaseHas('alerts', [
        'company_id' => $fixtures['company']->id,
        'traccar_event_id' => 1002,
        'type' => 'overspeed',
    ]);
});

it('converts traccar speed values from knots to kilometres per hour', function (): void {
    $fixtures = createOverspeedFixtures();

    // 32.4 knots ≈ 60 km/h
    // 27.0 knots ≈ 50 km/h
    $event = createOverspeedEvent(
        device: $fixtures['device'],
        speed: 32.4,
        speedLimit: 27.0,
        traccarEventId: 900002,
    );

    $alert = app(CreateOverspeedAlert::class)->execute($event);

    expect($alert->message)
        ->toBe(
            "{$fixtures['vehicle']->registration_number} was travelling at 60 km/h in a 50 km/h zone."
        );
});

it('rejects an overspeed event when the device has no vehicle', function (): void {
    $company = Company::factory()->create();

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'traccar_device_id' => 123456,
    ]);

    $event = createOverspeedEvent($device);

    expect(
        fn () => app(CreateOverspeedAlert::class)->execute($event),
    )->toThrow(
        RuntimeException::class,
        "Device [{$device->id}] is not assigned to a vehicle.",
    );

    expect(Alert::query()->count())->toBe(0);
});

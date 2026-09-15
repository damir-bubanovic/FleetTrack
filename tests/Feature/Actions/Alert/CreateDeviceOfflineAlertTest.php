<?php

declare(strict_types=1);

use App\Actions\Alert\CreateDeviceOfflineAlert;
use App\Events\DeviceWentOffline;
use App\Models\Alert;
use App\Models\Company;
use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createDeviceOfflineFixtures(): array
{
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
        'name' => 'Tracker 01',
    ]);

    return [
        'company' => $company,
        'vehicle' => $vehicle,
        'device' => $device,
    ];
}

function createDeviceWentOfflineEvent(
    Device $device,
    int $traccarEventId = 900001,
): DeviceWentOffline {
    return new DeviceWentOffline(
        device: $device,
        traccarEventId: $traccarEventId,
        occurredAt: new DateTimeImmutable(
            '2026-09-15T10:30:00Z',
        ),
    );
}

it('creates a device offline alert', function (): void {
    $fixtures = createDeviceOfflineFixtures();

    $alert = app(CreateDeviceOfflineAlert::class)->execute(
        createDeviceWentOfflineEvent($fixtures['device']),
    );

    expect($alert)
        ->toBeInstanceOf(Alert::class)
        ->and($alert->company_id)->toBe($fixtures['company']->id)
        ->and($alert->vehicle_id)->toBe($fixtures['vehicle']->id)
        ->and($alert->device_id)->toBe($fixtures['device']->id)
        ->and($alert->geofence_id)->toBeNull()
        ->and($alert->type)->toBe('device_offline')
        ->and($alert->severity)->toBe('warning')
        ->and($alert->title)->toBe('Device went offline')
        ->and($alert->message)->toBe(
            "{$fixtures['vehicle']->registration_number} device Tracker 01 went offline."
        )
        ->and($alert->traccar_event_id)->toBe(900001)
        ->and($alert->acknowledged_at)->toBeNull()
        ->and($alert->acknowledged_by)->toBeNull();

    expect($alert->occurred_at->toAtomString())
        ->toBe('2026-09-15T10:30:00+00:00');
});

it('does not create a duplicate device offline alert for the same traccar event', function (): void {
    $fixtures = createDeviceOfflineFixtures();

    $event = createDeviceWentOfflineEvent(
        $fixtures['device'],
    );

    $action = app(CreateDeviceOfflineAlert::class);

    $firstAlert = $action->execute($event);
    $secondAlert = $action->execute($event);

    expect($secondAlert->id)
        ->toBe($firstAlert->id)
        ->and(Alert::query()->count())
        ->toBe(1);
});

it('creates separate alerts for different device offline events', function (): void {
    $fixtures = createDeviceOfflineFixtures();

    $action = app(CreateDeviceOfflineAlert::class);

    $action->execute(
        createDeviceWentOfflineEvent(
            device: $fixtures['device'],
            traccarEventId: 1001,
        ),
    );

    $action->execute(
        createDeviceWentOfflineEvent(
            device: $fixtures['device'],
            traccarEventId: 1002,
        ),
    );

    expect(Alert::query()->count())->toBe(2);

    $this->assertDatabaseHas('alerts', [
        'company_id' => $fixtures['company']->id,
        'traccar_event_id' => 1001,
        'type' => 'device_offline',
        'severity' => 'warning',
    ]);

    $this->assertDatabaseHas('alerts', [
        'company_id' => $fixtures['company']->id,
        'traccar_event_id' => 1002,
        'type' => 'device_offline',
        'severity' => 'warning',
    ]);
});

it('uses the vehicle registration and device name in the alert message', function (): void {
    $fixtures = createDeviceOfflineFixtures();

    $fixtures['vehicle']->update([
        'registration_number' => 'ZG-1234-AB',
    ]);

    $fixtures['device']->update([
        'name' => 'Main GPS',
    ]);

    $alert = app(CreateDeviceOfflineAlert::class)->execute(
        createDeviceWentOfflineEvent(
            device: $fixtures['device'],
            traccarEventId: 900002,
        ),
    );

    expect($alert->message)
        ->toBe('ZG-1234-AB device Main GPS went offline.');
});

it('rejects a device offline event when the device has no vehicle', function (): void {
    $company = Company::factory()->create();

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'traccar_device_id' => 123456,
        'name' => 'Tracker 01',
    ]);

    $event = createDeviceWentOfflineEvent($device);

    expect(
        fn () => app(CreateDeviceOfflineAlert::class)->execute($event),
    )->toThrow(
        RuntimeException::class,
        "Device [{$device->id}] is not assigned to a vehicle.",
    );

    expect(Alert::query()->count())->toBe(0);
});

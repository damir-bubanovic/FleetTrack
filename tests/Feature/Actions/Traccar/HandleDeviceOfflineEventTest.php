<?php

declare(strict_types=1);

use App\Actions\Traccar\HandleDeviceOfflineEvent;
use App\Data\Traccar\DeviceOfflineEventData;
use App\Events\DeviceWentOffline;
use App\Models\Company;
use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Event::fake();
});

function makeDeviceOfflineEventData(
    int $deviceId = 123456,
): DeviceOfflineEventData {
    return new DeviceOfflineEventData(
        eventId: 987654,
        deviceId: $deviceId,
        eventTime: new DateTimeImmutable(
            '2026-09-15T10:30:00Z',
        ),
    );
}

it('dispatches a device offline event for a valid device and vehicle', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
    ]);

    app(HandleDeviceOfflineEvent::class)->execute(
        makeDeviceOfflineEventData(),
    );

    Event::assertDispatched(
        DeviceWentOffline::class,
        function (DeviceWentOffline $event) use ($device): bool {
            return $event->device->is($device)
                && $event->traccarEventId === 987654
                && $event->occurredAt->format(DATE_ATOM)
                    === '2026-09-15T10:30:00+00:00';
        },
    );
});

it('rejects an unknown traccar device', function (): void {
    expect(
        fn () => app(HandleDeviceOfflineEvent::class)->execute(
            makeDeviceOfflineEventData(deviceId: 999999),
        ),
    )->toThrow(
        ModelNotFoundException::class,
    );

    Event::assertNotDispatched(DeviceWentOffline::class);
});

it('rejects a device without an assigned vehicle', function (): void {
    $company = Company::factory()->create();

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'traccar_device_id' => 123456,
    ]);

    expect(
        fn () => app(HandleDeviceOfflineEvent::class)->execute(
            makeDeviceOfflineEventData(),
        ),
    )->toThrow(
        RuntimeException::class,
        "Device [{$device->id}] is not assigned to a vehicle.",
    );

    Event::assertNotDispatched(DeviceWentOffline::class);
});

it('rejects a device assigned to a vehicle from another company', function (): void {
    $deviceCompany = Company::factory()->create();
    $vehicleCompany = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $vehicleCompany->id,
    ]);

    Device::factory()->create([
        'company_id' => $deviceCompany->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
    ]);

    expect(
        fn () => app(HandleDeviceOfflineEvent::class)->execute(
            makeDeviceOfflineEventData(),
        ),
    )->toThrow(
        RuntimeException::class,
        'Device and assigned vehicle belong to different companies.',
    );

    Event::assertNotDispatched(DeviceWentOffline::class);
});

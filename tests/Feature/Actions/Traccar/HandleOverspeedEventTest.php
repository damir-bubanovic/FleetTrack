<?php

declare(strict_types=1);

use App\Actions\Traccar\HandleOverspeedEvent;
use App\Data\Traccar\OverspeedEventData;
use App\Events\OverspeedOccurred;
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

function makeOverspeedEventData(
    int $deviceId = 123456,
): OverspeedEventData {
    return new OverspeedEventData(
        eventId: 987654,
        deviceId: $deviceId,
        positionId: 456789,
        eventTime: new DateTimeImmutable('2026-09-15T08:30:00Z'),
        speed: 54.0,
        speedLimit: 43.2,
    );
}

it('dispatches an overspeed event for a valid device and vehicle', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
    ]);

    app(HandleOverspeedEvent::class)->execute(
        makeOverspeedEventData(),
    );

    Event::assertDispatched(
        OverspeedOccurred::class,
        function (OverspeedOccurred $event) use ($device): bool {
            return $event->device->is($device)
                && $event->positionId === 456789
                && $event->speed === 54.0
                && $event->speedLimit === 43.2
                && $event->traccarEventId === 987654
                && $event->occurredAt->format(DATE_ATOM)
                    === '2026-09-15T08:30:00+00:00';
        },
    );
});

it('rejects an unknown traccar device', function (): void {
    expect(
        fn () => app(HandleOverspeedEvent::class)->execute(
            makeOverspeedEventData(deviceId: 999999),
        ),
    )->toThrow(
        ModelNotFoundException::class,
    );

    Event::assertNotDispatched(OverspeedOccurred::class);
});

it('rejects a device without an assigned vehicle', function (): void {
    $company = Company::factory()->create();

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'traccar_device_id' => 123456,
    ]);

    expect(
        fn () => app(HandleOverspeedEvent::class)->execute(
            makeOverspeedEventData(),
        ),
    )->toThrow(
        RuntimeException::class,
        "Device [{$device->id}] is not assigned to a vehicle.",
    );

    Event::assertNotDispatched(OverspeedOccurred::class);
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
        fn () => app(HandleOverspeedEvent::class)->execute(
            makeOverspeedEventData(),
        ),
    )->toThrow(
        RuntimeException::class,
        'Device and assigned vehicle belong to different companies.',
    );

    Event::assertNotDispatched(OverspeedOccurred::class);
});

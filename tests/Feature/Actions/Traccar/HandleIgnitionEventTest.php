<?php

declare(strict_types=1);

use App\Actions\Traccar\HandleIgnitionEvent;
use App\Data\Traccar\IgnitionEventData;
use App\Events\IgnitionChanged;
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

function makeIgnitionEventData(
    string $type = 'ignitionOn',
    int $deviceId = 123456,
): IgnitionEventData {
    return new IgnitionEventData(
        eventId: 987654,
        type: $type,
        deviceId: $deviceId,
        positionId: 456789,
        eventTime: new DateTimeImmutable(
            '2026-09-15T09:30:00Z',
        ),
    );
}

it('dispatches an ignition on event for a valid device and vehicle', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
    ]);

    app(HandleIgnitionEvent::class)->execute(
        makeIgnitionEventData(),
    );

    Event::assertDispatched(
        IgnitionChanged::class,
        function (IgnitionChanged $event) use ($device): bool {
            return $event->device->is($device)
                && $event->type === 'ignitionOn'
                && $event->positionId === 456789
                && $event->traccarEventId === 987654
                && $event->occurredAt->format(DATE_ATOM)
                    === '2026-09-15T09:30:00+00:00';
        },
    );
});

it('dispatches an ignition off event for a valid device and vehicle', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
    ]);

    app(HandleIgnitionEvent::class)->execute(
        makeIgnitionEventData(type: 'ignitionOff'),
    );

    Event::assertDispatched(
        IgnitionChanged::class,
        fn (IgnitionChanged $event): bool => $event->type === 'ignitionOff'
            && $event->device->is($device),
    );
});

it('rejects an unsupported ignition event type', function (): void {
    expect(
        fn () => app(HandleIgnitionEvent::class)->execute(
            makeIgnitionEventData(type: 'unknownIgnition'),
        ),
    )->toThrow(
        RuntimeException::class,
        'Unsupported Traccar ignition event type [unknownIgnition].',
    );

    Event::assertNotDispatched(IgnitionChanged::class);
});

it('rejects an unknown traccar device', function (): void {
    expect(
        fn () => app(HandleIgnitionEvent::class)->execute(
            makeIgnitionEventData(deviceId: 999999),
        ),
    )->toThrow(
        ModelNotFoundException::class,
    );

    Event::assertNotDispatched(IgnitionChanged::class);
});

it('rejects a device without an assigned vehicle', function (): void {
    $company = Company::factory()->create();

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'traccar_device_id' => 123456,
    ]);

    expect(
        fn () => app(HandleIgnitionEvent::class)->execute(
            makeIgnitionEventData(),
        ),
    )->toThrow(
        RuntimeException::class,
        "Device [{$device->id}] is not assigned to a vehicle.",
    );

    Event::assertNotDispatched(IgnitionChanged::class);
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
        fn () => app(HandleIgnitionEvent::class)->execute(
            makeIgnitionEventData(),
        ),
    )->toThrow(
        RuntimeException::class,
        'Device and assigned vehicle belong to different companies.',
    );

    Event::assertNotDispatched(IgnitionChanged::class);
});

<?php

declare(strict_types=1);

use App\Events\IgnitionChanged;
use App\Models\Company;
use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'services.traccar.webhook_token' => 'test-traccar-webhook-token',
    ]);

    Event::fake();

    $this->company = Company::factory()->create();

    $this->vehicle = Vehicle::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->device = Device::factory()->create([
        'company_id' => $this->company->id,
        'vehicle_id' => $this->vehicle->id,
        'traccar_device_id' => 123456,
    ]);

    $this->payload = [
        'id' => 987654,
        'type' => 'ignitionOn',
        'deviceId' => 123456,
        'positionId' => 456789,
        'eventTime' => '2026-09-15T09:30:00Z',
    ];
});

it('validates the traccar ignition event payload', function (): void {
    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', [
            'type' => 'ignitionOn',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'id',
            'deviceId',
            'positionId',
            'eventTime',
        ]);

    Event::assertNotDispatched(IgnitionChanged::class);
});

it('does not require a geofence or overspeed attributes for an ignition event', function (): void {
    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertOk()
        ->assertJson([
            'message' => 'Event processed successfully.',
        ]);

    Event::assertDispatched(IgnitionChanged::class);
});

it('processes a valid ignition on event', function (): void {
    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertOk();

    Event::assertDispatched(
        IgnitionChanged::class,
        function (IgnitionChanged $event): bool {
            return $event->device->is($this->device)
                && $event->type === 'ignitionOn'
                && $event->positionId === 456789
                && $event->traccarEventId === 987654
                && $event->occurredAt->format(DATE_ATOM)
                    === '2026-09-15T09:30:00+00:00';
        },
    );
});

it('processes a valid ignition off event', function (): void {
    $payload = $this->payload;

    $payload['id'] = 987655;
    $payload['type'] = 'ignitionOff';

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $payload)
        ->assertOk();

    Event::assertDispatched(
        IgnitionChanged::class,
        fn (IgnitionChanged $event): bool => $event->type === 'ignitionOff'
            && $event->traccarEventId === 987655
            && $event->device->is($this->device),
    );
});

it('rejects an ignition event for an unknown traccar device', function (): void {
    $payload = $this->payload;
    $payload['deviceId'] = 999999;

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $payload)
        ->assertNotFound();

    Event::assertNotDispatched(IgnitionChanged::class);
});

it('rejects an ignition event when the device has no vehicle', function (): void {
    $this->device->update([
        'vehicle_id' => null,
    ]);

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertServerError();

    Event::assertNotDispatched(IgnitionChanged::class);
});

it('rejects an ignition event when device and vehicle belong to different companies', function (): void {
    $otherCompany = Company::factory()->create();

    $this->vehicle->update([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertServerError();

    Event::assertNotDispatched(IgnitionChanged::class);
});

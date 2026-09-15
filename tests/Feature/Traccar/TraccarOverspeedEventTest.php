<?php

declare(strict_types=1);

use App\Events\OverspeedOccurred;
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
        'type' => 'deviceOverspeed',
        'deviceId' => 123456,
        'positionId' => 456789,
        'eventTime' => '2026-09-15T08:30:00Z',
        'attributes' => [
            'speed' => 54.0,
            'speedLimit' => 43.2,
        ],
    ];
});

it('validates the traccar overspeed event payload', function (): void {
    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', [
            'type' => 'deviceOverspeed',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'id',
            'deviceId',
            'positionId',
            'eventTime',
            'attributes',
            'attributes.speed',
            'attributes.speedLimit',
        ]);

    Event::assertNotDispatched(OverspeedOccurred::class);
});

it('does not require a geofence for an overspeed event', function (): void {
    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertOk()
        ->assertJson([
            'message' => 'Event processed successfully.',
        ]);

    Event::assertDispatched(OverspeedOccurred::class);
});

it('processes a valid overspeed event', function (): void {
    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertOk();

    Event::assertDispatched(
        OverspeedOccurred::class,
        function (OverspeedOccurred $event): bool {
            return $event->device->is($this->device)
                && $event->positionId === 456789
                && $event->speed === 54.0
                && $event->speedLimit === 43.2
                && $event->traccarEventId === 987654
                && $event->occurredAt->format(DATE_ATOM)
                    === '2026-09-15T08:30:00+00:00';
        },
    );
});

it('rejects an overspeed event for an unknown traccar device', function (): void {
    $payload = $this->payload;
    $payload['deviceId'] = 999999;

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $payload)
        ->assertNotFound();

    Event::assertNotDispatched(OverspeedOccurred::class);
});

it('rejects an overspeed event when the device has no vehicle', function (): void {
    $this->device->update([
        'vehicle_id' => null,
    ]);

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertServerError();

    Event::assertNotDispatched(OverspeedOccurred::class);
});

it('rejects an overspeed event when device and vehicle belong to different companies', function (): void {
    $otherCompany = Company::factory()->create();

    $this->vehicle->update([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertServerError();

    Event::assertNotDispatched(OverspeedOccurred::class);
});

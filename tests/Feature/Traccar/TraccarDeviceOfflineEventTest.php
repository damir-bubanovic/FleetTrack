<?php

declare(strict_types=1);

use App\Events\DeviceWentOffline;
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
        'type' => 'deviceOffline',
        'deviceId' => 123456,
        'eventTime' => '2026-09-15T10:30:00Z',
    ];
});

it('validates the traccar device offline event payload', function (): void {
    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', [
            'type' => 'deviceOffline',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'id',
            'deviceId',
            'eventTime',
        ]);

    Event::assertNotDispatched(DeviceWentOffline::class);
});

it('does not require position, geofence, or overspeed attributes', function (): void {
    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertOk()
        ->assertJson([
            'message' => 'Event processed successfully.',
        ]);

    Event::assertDispatched(DeviceWentOffline::class);
});

it('processes a valid device offline event', function (): void {
    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertOk();

    Event::assertDispatched(
        DeviceWentOffline::class,
        function (DeviceWentOffline $event): bool {
            return $event->device->is($this->device)
                && $event->traccarEventId === 987654
                && $event->occurredAt->format(DATE_ATOM)
                    === '2026-09-15T10:30:00+00:00';
        },
    );
});

it('rejects a device offline event for an unknown traccar device', function (): void {
    $payload = $this->payload;
    $payload['deviceId'] = 999999;

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $payload)
        ->assertNotFound();

    Event::assertNotDispatched(DeviceWentOffline::class);
});

it('rejects a device offline event when the device has no vehicle', function (): void {
    $this->device->update([
        'vehicle_id' => null,
    ]);

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertServerError();

    Event::assertNotDispatched(DeviceWentOffline::class);
});

it('rejects a device offline event when device and vehicle belong to different companies', function (): void {
    $otherCompany = Company::factory()->create();

    $this->vehicle->update([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $this->payload)
        ->assertServerError();

    Event::assertNotDispatched(DeviceWentOffline::class);
});

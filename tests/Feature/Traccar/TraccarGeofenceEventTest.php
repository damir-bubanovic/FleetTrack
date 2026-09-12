<?php

declare(strict_types=1);

use App\Events\GeofenceTransitionOccurred;
use App\Models\Company;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'services.traccar.webhook_token' => 'test-traccar-webhook-token',
    ]);

    Event::fake();
});

function createTraccarGeofenceEventFixtures(): array
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

function validTraccarGeofenceEventPayload(): array
{
    return [
        'id' => 987654,
        'type' => 'geofenceEnter',
        'deviceId' => 123456,
        'geofenceId' => 654321,
        'eventTime' => '2026-09-12T10:30:00Z',
    ];
}

it('rejects a traccar event without a webhook token', function (): void {
    $this
        ->postJson(
            '/api/v1/traccar/events',
            validTraccarGeofenceEventPayload(),
        )
        ->assertUnauthorized();

    Event::assertNotDispatched(GeofenceTransitionOccurred::class);
});

it('rejects a traccar event with an invalid webhook token', function (): void {
    $this
        ->withToken('wrong-token')
        ->postJson(
            '/api/v1/traccar/events',
            validTraccarGeofenceEventPayload(),
        )
        ->assertUnauthorized();

    Event::assertNotDispatched(GeofenceTransitionOccurred::class);
});

it('validates the traccar geofence event payload', function (): void {
    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'id',
            'type',
            'deviceId',
            'geofenceId',
            'eventTime',
        ]);

    Event::assertNotDispatched(GeofenceTransitionOccurred::class);
});

it('rejects unsupported traccar event types', function (): void {
    $payload = validTraccarGeofenceEventPayload();
    $payload['type'] = 'deviceOnline';

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('type');

    Event::assertNotDispatched(GeofenceTransitionOccurred::class);
});

it('processes a valid geofence enter event', function (): void {
    $fixtures = createTraccarGeofenceEventFixtures();

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson(
            '/api/v1/traccar/events',
            validTraccarGeofenceEventPayload(),
        )
        ->assertOk()
        ->assertJson([
            'message' => 'Event processed successfully.',
        ]);

    Event::assertDispatched(
        GeofenceTransitionOccurred::class,
        function (GeofenceTransitionOccurred $event) use ($fixtures): bool {
            return $event->device->is($fixtures['device'])
                && $event->geofence->is($fixtures['geofence'])
                && $event->type === 'geofenceEnter'
                && $event->traccarEventId === 987654
                && $event->occurredAt->format(DATE_ATOM)
                    === '2026-09-12T10:30:00+00:00';
        },
    );
});

it('processes a valid geofence exit event', function (): void {
    createTraccarGeofenceEventFixtures();

    $payload = validTraccarGeofenceEventPayload();
    $payload['type'] = 'geofenceExit';

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $payload)
        ->assertOk();

    Event::assertDispatched(
        GeofenceTransitionOccurred::class,
        fn (GeofenceTransitionOccurred $event): bool => $event->type === 'geofenceExit',
    );
});

it('does not process an event for an unknown traccar device', function (): void {
    createTraccarGeofenceEventFixtures();

    $payload = validTraccarGeofenceEventPayload();
    $payload['deviceId'] = 999999;

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $payload)
        ->assertNotFound();

    Event::assertNotDispatched(GeofenceTransitionOccurred::class);
});

it('does not process an event for an unknown traccar geofence', function (): void {
    createTraccarGeofenceEventFixtures();

    $payload = validTraccarGeofenceEventPayload();
    $payload['geofenceId'] = 999999;

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson('/api/v1/traccar/events', $payload)
        ->assertNotFound();

    Event::assertNotDispatched(GeofenceTransitionOccurred::class);
});

it('does not process an event when device and geofence belong to different companies', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => 123456,
    ]);

    Geofence::factory()->create([
        'company_id' => $otherCompany->id,
        'traccar_geofence_id' => 654321,
    ]);

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson(
            '/api/v1/traccar/events',
            validTraccarGeofenceEventPayload(),
        )
        ->assertServerError();

    Event::assertNotDispatched(GeofenceTransitionOccurred::class);
});

it('does not process an event when the device has no vehicle', function (): void {
    $company = Company::factory()->create();

    Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'traccar_device_id' => 123456,
    ]);

    Geofence::factory()->create([
        'company_id' => $company->id,
        'traccar_geofence_id' => 654321,
    ]);

    $this
        ->withToken('test-traccar-webhook-token')
        ->postJson(
            '/api/v1/traccar/events',
            validTraccarGeofenceEventPayload(),
        )
        ->assertServerError();

    Event::assertNotDispatched(GeofenceTransitionOccurred::class);
});

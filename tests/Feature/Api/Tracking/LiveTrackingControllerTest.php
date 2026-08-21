<?php

use App\Models\Fleet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesDevices;
use Tests\Traits\CreatesUsers;
use Tests\Traits\CreatesVehicles;

uses(
    RefreshDatabase::class,
    CreatesCompanies::class,
    CreatesUsers::class,
    CreatesVehicles::class,
    CreatesDevices::class,
);

test('company admin can view live positions for own company devices', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $device = $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
                'altitude' => 120.5,
                'speed' => 42.3,
                'course' => 180.0,
                'accuracy' => 5.0,
                'fixTime' => '2026-08-18T17:00:00.000+00:00',
                'serverTime' => '2026-08-18T17:00:01.000+00:00',
                'attributes' => [
                    'ignition' => true,
                ],
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson('/api/v1/tracking/positions');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.device.id', $device->id)
        ->assertJsonPath('data.0.device.traccar_device_id', 101)
        ->assertJsonPath('data.0.vehicle.id', $vehicle->id)
        ->assertJsonPath('data.0.position.device_id', 101)
        ->assertJsonPath('data.0.position.latitude', 45.8150)
        ->assertJsonPath('data.0.position.longitude', 15.9819)
        ->assertJsonPath('data.0.position.attributes.ignition', true);
});

test('company admin cannot see live positions from another company', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleA = $this->createVehicle($companyA, $fleetA);
    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $deviceA = $this->createDevice($companyA, $vehicleA, [
        'traccar_device_id' => 101,
    ]);

    $this->createDevice($companyB, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
            ],
            [
                'id' => 1002,
                'deviceId' => 202,
                'latitude' => 44.8666,
                'longitude' => 13.8496,
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($companyA);

    $response = $this->getJson('/api/v1/tracking/positions');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.device.id', $deviceA->id)
        ->assertJsonPath('data.0.position.device_id', 101);

    $response->assertJsonMissing([
        'device_id' => 202,
    ]);
});

test('super admin can view live positions from all companies', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleA = $this->createVehicle($companyA, $fleetA);
    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevice($companyA, $vehicleA, [
        'traccar_device_id' => 101,
    ]);

    $this->createDevice($companyB, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
            ],
            [
                'id' => 1002,
                'deviceId' => 202,
                'latitude' => 44.8666,
                'longitude' => 13.8496,
            ],
        ], 200),
    ]);

    $this->actingAsSuperAdmin();

    $this->getJson('/api/v1/tracking/positions')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('unsynced devices do not expose traccar positions', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => null,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/tracking/positions')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('company admin can view live position for own vehicle', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $device = $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
                'speed' => 42.3,
                'attributes' => [
                    'ignition' => true,
                ],
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson("/api/v1/tracking/vehicles/{$vehicle->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.device.id', $device->id)
        ->assertJsonPath('data.device.traccar_device_id', 101)
        ->assertJsonPath('data.vehicle.id', $vehicle->id)
        ->assertJsonPath('data.position.device_id', 101)
        ->assertJsonPath('data.position.latitude', 45.8150)
        ->assertJsonPath('data.position.longitude', 15.9819)
        ->assertJsonPath('data.position.attributes.ignition', true);
});

test('company admin cannot view live position for another company vehicle', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevice($companyB, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1002,
                'deviceId' => 202,
                'latitude' => 44.8666,
                'longitude' => 13.8496,
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson("/api/v1/tracking/vehicles/{$vehicleB->id}")
        ->assertNotFound();
});

test('vehicle without synced device has no live position', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => null,
    ]);

    Http::fake();

    $this->actingAsCompanyAdmin($company);

    $this->getJson("/api/v1/tracking/vehicles/{$vehicle->id}")
        ->assertNotFound();

    Http::assertNothingSent();
});

test('vehicle returns not found when traccar has no live position', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson("/api/v1/tracking/vehicles/{$vehicle->id}")
        ->assertNotFound();
});

test('company admin can filter live positions by fleet', function (): void {
    $company = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicleA = $this->createVehicle($company, $fleetA);
    $vehicleB = $this->createVehicle($company, $fleetB);

    $deviceA = $this->createDevice($company, $vehicleA, [
        'traccar_device_id' => 101,
    ]);

    $this->createDevice($company, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
            ],
            [
                'id' => 1002,
                'deviceId' => 202,
                'latitude' => 44.8666,
                'longitude' => 13.8496,
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson(
        "/api/v1/tracking/positions?fleet_id={$fleetA->id}"
    );

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.device.id', $deviceA->id)
        ->assertJsonPath('data.0.vehicle.id', $vehicleA->id)
        ->assertJsonPath('data.0.position.device_id', 101);

    $response->assertJsonMissing([
        'device_id' => 202,
    ]);
});

test('fleet filter does not expose another company live positions', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevice($companyB, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1002,
                'deviceId' => 202,
                'latitude' => 44.8666,
                'longitude' => 13.8496,
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson(
        "/api/v1/tracking/positions?fleet_id={$fleetB->id}"
    )
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('fleet filter must contain a valid fleet id', function (): void {
    $company = $this->createCompany();

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/tracking/positions?fleet_id=999999')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('fleet_id');
});

test('fleet filter must be an integer', function (): void {
    $company = $this->createCompany();

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/tracking/positions?fleet_id=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('fleet_id');
});

test('company admin can filter live positions by vehicle', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicleA = $this->createVehicle($company, $fleet);
    $vehicleB = $this->createVehicle($company, $fleet);

    $deviceA = $this->createDevice($company, $vehicleA, [
        'traccar_device_id' => 101,
    ]);

    $this->createDevice($company, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
            ],
            [
                'id' => 1002,
                'deviceId' => 202,
                'latitude' => 44.8666,
                'longitude' => 13.8496,
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson(
        "/api/v1/tracking/positions?vehicle_id={$vehicleA->id}"
    );

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.device.id', $deviceA->id)
        ->assertJsonPath('data.0.vehicle.id', $vehicleA->id)
        ->assertJsonPath('data.0.position.device_id', 101);

    $response->assertJsonMissing([
        'device_id' => 202,
    ]);
});

test('vehicle filter does not expose another company live position', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevice($companyB, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1002,
                'deviceId' => 202,
                'latitude' => 44.8666,
                'longitude' => 13.8496,
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson(
        "/api/v1/tracking/positions?vehicle_id={$vehicleB->id}"
    )
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('vehicle filter must contain a valid vehicle id', function (): void {
    $company = $this->createCompany();

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/tracking/positions?vehicle_id=999999')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('vehicle_id');
});

test('vehicle filter must be an integer', function (): void {
    $company = $this->createCompany();

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/tracking/positions?vehicle_id=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('vehicle_id');
});

test('company admin can combine fleet and vehicle live position filters', function (): void {
    $company = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicleA = $this->createVehicle($company, $fleetA);
    $vehicleB = $this->createVehicle($company, $fleetB);

    $deviceA = $this->createDevice($company, $vehicleA, [
        'traccar_device_id' => 101,
    ]);

    $this->createDevice($company, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
            ],
            [
                'id' => 1002,
                'deviceId' => 202,
                'latitude' => 44.8666,
                'longitude' => 13.8496,
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson(
        "/api/v1/tracking/positions?fleet_id={$fleetA->id}&vehicle_id={$vehicleA->id}"
    );

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.device.id', $deviceA->id)
        ->assertJsonPath('data.0.vehicle.id', $vehicleA->id)
        ->assertJsonPath('data.0.position.device_id', 101);
});

test('live position reports vehicle as online when gps fix is recent', function (): void {
    $this->travelTo('2026-08-19 10:00:00');

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
                'fixTime' => '2026-08-19T09:58:00+00:00',
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/tracking/positions')
        ->assertOk()
        ->assertJsonPath('data.0.status.online', true)
        ->assertJsonPath(
            'data.0.status.last_seen_at',
            '2026-08-19T09:58:00.000000Z'
        );
});

test('live position reports vehicle as offline when gps fix is stale', function (): void {
    $this->travelTo('2026-08-19 10:00:00');

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
                'fixTime' => '2026-08-19T09:50:00+00:00',
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/tracking/positions')
        ->assertOk()
        ->assertJsonPath('data.0.status.online', false)
        ->assertJsonPath(
            'data.0.status.last_seen_at',
            '2026-08-19T09:50:00.000000Z'
        );
});

test('live position reports vehicle as offline when gps fix time is missing', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/tracking/positions')
        ->assertOk()
        ->assertJsonPath('data.0.status.online', false)
        ->assertJsonPath('data.0.status.last_seen_at', null);
});

test('company admin can view position history for own vehicle', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
                'speed' => 35.5,
                'fixTime' => '2026-08-18T08:30:00+00:00',
                'attributes' => [
                    'ignition' => true,
                ],
            ],
            [
                'id' => 1002,
                'deviceId' => 101,
                'latitude' => 45.8200,
                'longitude' => 15.9900,
                'speed' => 42.0,
                'fixTime' => '2026-08-18T09:00:00+00:00',
                'attributes' => [
                    'ignition' => true,
                ],
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/positions"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    );

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', 1001)
        ->assertJsonPath('data.0.device_id', 101)
        ->assertJsonPath('data.0.latitude', 45.8150)
        ->assertJsonPath('data.0.longitude', 15.9819)
        ->assertJsonPath('data.0.attributes.ignition', true)
        ->assertJsonPath('data.1.id', 1002);
});

test('position history sends device and time range to traccar', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/positions"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )->assertOk();

    Http::assertSent(function ($request): bool {
        parse_str(
            (string) parse_url($request->url(), PHP_URL_QUERY),
            $query
        );

        return ($query['deviceId'] ?? null) === '101'
            && ($query['from'] ?? null) === '2026-08-18T08:00:00.000000Z'
            && ($query['to'] ?? null) === '2026-08-18T10:00:00.000000Z';
    });
});

test('company admin cannot view position history for another company vehicle', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevice($companyB, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake();

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicleB->id}/positions"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )
        ->assertOk()
        ->assertJsonCount(0, 'data');

    Http::assertNothingSent();
});

test('position history requires from date', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/positions"
        .'?to=2026-08-18T10:00:00Z'
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('from');
});

test('position history requires to date', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/positions"
        .'?from=2026-08-18T08:00:00Z'
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');
});

test('position history to date must be after from date', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/positions"
        .'?from=2026-08-18T10:00:00Z'
        .'&to=2026-08-18T08:00:00Z'
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');
});

test('vehicle without synced device has no position history', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => null,
    ]);

    Http::fake();

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/positions"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )
        ->assertOk()
        ->assertJsonCount(0, 'data');

    Http::assertNothingSent();
});

test('position history allows a seven day date range', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/positions"
        .'?from=2026-08-01T10:00:00Z'
        .'&to=2026-08-08T10:00:00Z'
    )->assertOk();

    Http::assertSentCount(1);
});

test('position history rejects a date range longer than seven days', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    Http::fake();

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/positions"
        .'?from=2026-08-01T10:00:00Z'
        .'&to=2026-08-08T10:00:01Z'
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');

    Http::assertNothingSent();
});

test('company admin can view trip summary for own vehicle', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
                'speed' => 10.0,
                'fixTime' => '2026-08-18T08:00:00+00:00',
            ],
            [
                'id' => 1002,
                'deviceId' => 101,
                'latitude' => 45.8200,
                'longitude' => 15.9900,
                'speed' => 0.0,
                'fixTime' => '2026-08-18T08:30:00+00:00',
            ],
            [
                'id' => 1003,
                'deviceId' => 101,
                'latitude' => 45.8250,
                'longitude' => 16.0000,
                'speed' => 30.0,
                'fixTime' => '2026-08-18T09:00:00+00:00',
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/trip-summary"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.position_count', 3)
        ->assertJsonPath('data.started_at', '2026-08-18T08:00:00+00:00')
        ->assertJsonPath('data.ended_at', '2026-08-18T09:00:00+00:00')
        ->assertJsonPath('data.duration_seconds', 3600)
        ->assertJsonPath('data.average_speed', 13.33)
        ->assertJsonPath('data.max_speed', 30)
        ->assertJsonPath('data.moving_seconds', 1800)
        ->assertJsonPath('data.stopped_seconds', 1800)
        ->assertJsonPath('data.speed_unit', 'knots');

    expect($response->json('data.distance_km'))
        ->toBeGreaterThan(0);
});

test('trip summary returns empty summary when vehicle has no position history', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/trip-summary"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )
        ->assertOk()
        ->assertJsonPath('data.position_count', 0)
        ->assertJsonPath('data.started_at', null)
        ->assertJsonPath('data.ended_at', null)
        ->assertJsonPath('data.duration_seconds', null)
        ->assertJsonPath('data.distance_km', 0)
        ->assertJsonPath('data.average_speed', 0)
        ->assertJsonPath('data.max_speed', 0)
        ->assertJsonPath('data.moving_seconds', 0)
        ->assertJsonPath('data.stopped_seconds', 0)
        ->assertJsonPath('data.speed_unit', 'knots');
});

test('company admin cannot view trip summary for another company vehicle', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevice($companyB, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake();

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicleB->id}/trip-summary"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )
        ->assertOk()
        ->assertJsonPath('data.position_count', 0)
        ->assertJsonPath('data.distance_km', 0);

    Http::assertNothingSent();
});

test('trip summary validates required date range', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/trip-summary"
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'from',
            'to',
        ]);
});

test('trip summary rejects a date range longer than seven days', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    Http::fake();

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/trip-summary"
        .'?from=2026-08-01T10:00:00Z'
        .'&to=2026-08-08T10:00:01Z'
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');

    Http::assertNothingSent();
});

test('trip summary handles positions without speed data', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
                'fixTime' => '2026-08-18T08:00:00+00:00',
            ],
            [
                'id' => 1002,
                'deviceId' => 101,
                'latitude' => 45.8200,
                'longitude' => 15.9900,
                'fixTime' => '2026-08-18T09:00:00+00:00',
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/tracking/vehicles/{$vehicle->id}/trip-summary"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )
        ->assertOk()
        ->assertJsonPath('data.average_speed', 0)
        ->assertJsonPath('data.max_speed', 0)
        ->assertJsonPath('data.moving_seconds', 0)
        ->assertJsonPath('data.stopped_seconds', 3600)
        ->assertJsonPath('data.speed_unit', 'knots');
});

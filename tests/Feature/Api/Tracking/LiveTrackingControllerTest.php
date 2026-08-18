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

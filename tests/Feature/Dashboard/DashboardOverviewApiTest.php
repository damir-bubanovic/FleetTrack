<?php

declare(strict_types=1);

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

test('company admin can view dashboard overview for own company', function (): void {
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

    $this->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.companies', 1)
        ->assertJsonPath('data.fleets', 1)
        ->assertJsonPath('data.vehicles', 1)
        ->assertJsonPath('data.devices', 1)
        ->assertJsonPath('data.online_vehicles', 0)
        ->assertJsonPath('data.offline_vehicles', 1);
});

test('company dashboard overview excludes another company data', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $vehicleA = $this->createVehicle($companyA, $fleetA);

    $this->createDevice($companyA, $vehicleA, [
        'traccar_device_id' => 101,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevice($companyB, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.companies', 1)
        ->assertJsonPath('data.fleets', 1)
        ->assertJsonPath('data.vehicles', 1)
        ->assertJsonPath('data.devices', 1)
        ->assertJsonPath('data.online_vehicles', 0)
        ->assertJsonPath('data.offline_vehicles', 1);
});

test('super admin dashboard overview includes all companies', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $vehicleA = $this->createVehicle($companyA, $fleetA);

    $this->createDevice($companyA, $vehicleA, [
        'traccar_device_id' => 101,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevice($companyB, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $this->actingAsSuperAdmin();

    $this->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.companies', 2)
        ->assertJsonPath('data.fleets', 2)
        ->assertJsonPath('data.vehicles', 2)
        ->assertJsonPath('data.devices', 2)
        ->assertJsonPath('data.online_vehicles', 0)
        ->assertJsonPath('data.offline_vehicles', 2);
});

test('dashboard reports vehicle as online when gps fix is recent', function (): void {
    $this->travelTo('2026-09-16 10:00:00');

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
                'fixTime' => '2026-09-16T09:58:00+00:00',
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.vehicles', 1)
        ->assertJsonPath('data.online_vehicles', 1)
        ->assertJsonPath('data.offline_vehicles', 0);
});

test('dashboard reports vehicle as offline when gps fix is stale', function (): void {
    $this->travelTo('2026-09-16 10:00:00');

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
                'fixTime' => '2026-09-16T09:50:00+00:00',
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.online_vehicles', 0)
        ->assertJsonPath('data.offline_vehicles', 1);
});

test('dashboard reports vehicle as offline when gps position is missing', function (): void {
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

    $this->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.online_vehicles', 0)
        ->assertJsonPath('data.offline_vehicles', 1);
});

test('dashboard online vehicle count is isolated to own company', function (): void {
    $this->travelTo('2026-09-16 10:00:00');

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $vehicleA = $this->createVehicle($companyA, $fleetA);

    $this->createDevice($companyA, $vehicleA, [
        'traccar_device_id' => 101,
    ]);

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
                'id' => 1001,
                'deviceId' => 101,
                'latitude' => 45.8150,
                'longitude' => 15.9819,
                'fixTime' => '2026-09-16T09:50:00+00:00',
            ],
            [
                'id' => 1002,
                'deviceId' => 202,
                'latitude' => 44.8666,
                'longitude' => 13.8496,
                'fixTime' => '2026-09-16T09:58:00+00:00',
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.vehicles', 1)
        ->assertJsonPath('data.online_vehicles', 0)
        ->assertJsonPath('data.offline_vehicles', 1);
});

test('dashboard overview requires authentication', function (): void {
    $this->getJson('/api/v1/dashboard/overview')
        ->assertUnauthorized();
});

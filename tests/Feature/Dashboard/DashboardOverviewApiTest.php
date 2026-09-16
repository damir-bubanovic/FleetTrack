<?php

declare(strict_types=1);

use App\Models\Fleet;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    $this->createDevice($company, $vehicle);

    $this->actingAsCompanyAdmin($company);

    $this->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.companies', 1)
        ->assertJsonPath('data.fleets', 1)
        ->assertJsonPath('data.vehicles', 1)
        ->assertJsonPath('data.devices', 1);
});

test('company dashboard overview excludes another company data', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $vehicleA = $this->createVehicle($companyA, $fleetA);

    $this->createDevice($companyA, $vehicleA);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevice($companyB, $vehicleB);

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.companies', 1)
        ->assertJsonPath('data.fleets', 1)
        ->assertJsonPath('data.vehicles', 1)
        ->assertJsonPath('data.devices', 1);
});

test('super admin dashboard overview includes all companies', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $vehicleA = $this->createVehicle($companyA, $fleetA);

    $this->createDevice($companyA, $vehicleA);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevice($companyB, $vehicleB);

    $this->actingAsSuperAdmin();

    $this->getJson('/api/v1/dashboard/overview')
        ->assertOk()
        ->assertJsonPath('data.companies', 2)
        ->assertJsonPath('data.fleets', 2)
        ->assertJsonPath('data.vehicles', 2)
        ->assertJsonPath('data.devices', 2);
});

test('dashboard overview requires authentication', function (): void {
    $this->getJson('/api/v1/dashboard/overview')
        ->assertUnauthorized();
});

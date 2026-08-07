<?php

use App\Models\Fleet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesUsers;
use Tests\Traits\CreatesVehicles;

uses(
    RefreshDatabase::class,
    CreatesCompanies::class,
    CreatesUsers::class,
    CreatesVehicles::class,
);

test('super admin can list vehicles', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->createVehicles($company, $fleet, 3);

    $this->actingAsSuperAdmin();

    $response = $this->getJson('/api/v1/vehicles');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});


test('company admin only sees vehicles from own company', function (): void {

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $this->createVehicles($companyA, $fleetA, 3);
    $this->createVehicles($companyB, $fleetB, 2);

    $this->actingAsCompanyAdmin($companyA);

    $response = $this->getJson('/api/v1/vehicles');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $response->assertJsonMissing([
        'company_id' => $companyB->id,
    ]);
});


test('company admin can view own vehicle', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson("/api/v1/vehicles/{$vehicle->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $vehicle->id)
        ->assertJsonPath('data.company_id', $company->id);
});


test('company admin cannot view vehicle from another company', function (): void {

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicle = $this->createVehicle($companyB, $fleetB);

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson("/api/v1/vehicles/{$vehicle->id}")
        ->assertForbidden();
});


test('company admin can create vehicle', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAsCompanyAdmin($company);

    $response = $this->postJson('/api/v1/vehicles', [
        'fleet_id' => $fleet->id,
        'registration_number' => 'ZG-1234-AA',
        'vin' => '1HGCM82633A123456',
        'manufacturer' => 'Mercedes-Benz',
        'model' => 'Actros',
        'year' => 2024,
        'color' => 'White',
        'fuel_type' => 'Diesel',
        'transmission' => 'Automatic',
        'odometer' => 120000,
        'notes' => 'Primary logistics truck.',
        'is_active' => true,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.company_id', $company->id)
        ->assertJsonPath('data.fleet_id', $fleet->id)
        ->assertJsonPath('data.registration_number', 'ZG-1234-AA');

    $this->assertDatabaseHas('vehicles', [
        'company_id' => $company->id,
        'fleet_id' => $fleet->id,
        'registration_number' => 'ZG-1234-AA',
        'vin' => '1HGCM82633A123456',
    ]);
});


test('company admin cannot create vehicle in another company fleet', function (): void {

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $this->actingAsCompanyAdmin($companyA);

    $this->postJson('/api/v1/vehicles', [
        'fleet_id' => $fleetB->id,
        'registration_number' => 'ZG-9999-AA',
        'vin' => '1HGCM82633A654321',
        'manufacturer' => 'Volvo',
        'model' => 'FH16',
        'year' => 2024,
        'fuel_type' => 'Diesel',
        'transmission' => 'Automatic',
    ])->assertForbidden();

    $this->assertDatabaseMissing('vehicles', [
        'registration_number' => 'ZG-9999-AA',
    ]);
});



test('registration number is required', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->postJson('/api/v1/vehicles', [
        'fleet_id' => $fleet->id,
        'vin' => '1HGCM82633A123456',
        'manufacturer' => 'Mercedes-Benz',
        'model' => 'Actros',
        'year' => 2024,
        'fuel_type' => 'Diesel',
        'transmission' => 'Automatic',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'registration_number',
        ]);
});


test('vin must be unique', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->createVehicle($company, $fleet, [
        'vin' => '1HGCM82633A123456',
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->postJson('/api/v1/vehicles', [
        'fleet_id' => $fleet->id,
        'registration_number' => 'ZG-2222-AA',
        'vin' => '1HGCM82633A123456',
        'manufacturer' => 'Mercedes-Benz',
        'model' => 'Actros',
        'year' => 2024,
        'fuel_type' => 'Diesel',
        'transmission' => 'Automatic',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'vin',
        ]);
});


test('company admin can update own vehicle', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $response = $this->putJson("/api/v1/vehicles/{$vehicle->id}", [
        'fleet_id' => $fleet->id,
        'registration_number' => 'ZG-5555-AA',
        'vin' => $vehicle->vin,
        'manufacturer' => 'Volvo',
        'model' => 'FH16',
        'year' => 2025,
        'color' => 'Blue',
        'fuel_type' => 'Diesel',
        'transmission' => 'Automatic',
        'odometer' => 150000,
        'notes' => 'Updated vehicle.',
        'is_active' => false,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.registration_number', 'ZG-5555-AA')
        ->assertJsonPath('data.manufacturer', 'Volvo');

    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'registration_number' => 'ZG-5555-AA',
        'manufacturer' => 'Volvo',
        'is_active' => false,
    ]);
});



test('company admin cannot update vehicle from another company', function (): void {

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicle = $this->createVehicle($companyB, $fleetB);

    $this->actingAsCompanyAdmin($companyA);

    $this->putJson("/api/v1/vehicles/{$vehicle->id}", [
        'fleet_id' => $fleetB->id,
        'registration_number' => 'HACKED',
        'vin' => $vehicle->vin,
        'manufacturer' => 'Hack',
        'model' => 'Hack',
        'year' => 2025,
        'fuel_type' => 'Diesel',
        'transmission' => 'Automatic',
    ])->assertForbidden();
});



test('company admin can delete own vehicle', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $this->deleteJson("/api/v1/vehicles/{$vehicle->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('vehicles', [
        'id' => $vehicle->id,
    ]);
});



test('company admin cannot delete vehicle from another company', function (): void {

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicle = $this->createVehicle($companyB, $fleetB);

    $this->actingAsCompanyAdmin($companyA);

    $this->deleteJson("/api/v1/vehicles/{$vehicle->id}")
        ->assertForbidden();
});
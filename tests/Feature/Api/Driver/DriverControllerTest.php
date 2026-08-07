<?php

use App\Models\Fleet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesDrivers;
use Tests\Traits\CreatesUsers;

uses(
    RefreshDatabase::class,
    CreatesUsers::class,
    CreatesCompanies::class,
    CreatesDrivers::class,
);

test('super admin can list drivers', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->createDrivers($company, $fleet, 3);

    $this->actingAsSuperAdmin();

    $response = $this->getJson('/api/v1/drivers');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('company admin only sees drivers from own company', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $this->createDrivers($companyA, $fleetA, 3);
    $this->createDrivers($companyB, $fleetB, 2);

    $this->actingAsCompanyAdmin($companyA);

    $response = $this->getJson('/api/v1/drivers');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $response->assertJsonMissing([
        'company_id' => $companyB->id,
    ]);
});

test('company admin can view own driver', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $driver = $this->createDriver($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson("/api/v1/drivers/{$driver->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $driver->id)
        ->assertJsonPath('data.company_id', $company->id);
});

test('company admin cannot view driver from another company', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $driver = $this->createDriver($companyB, $fleetB);

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson("/api/v1/drivers/{$driver->id}")
        ->assertForbidden();
});

test('company admin can create driver', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAsCompanyAdmin($company);

    $response = $this->postJson('/api/v1/drivers', [
        'fleet_id' => $fleet->id,
        'employee_number' => 'EMP-001',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '+38591123456',
        'email' => 'john.doe@example.com',
        'license_number' => 'LIC-001',
        'license_category' => 'C',
        'license_expiry_date' => now()->addYear()->toDateString(),
        'employment_date' => now()->subYear()->toDateString(),
        'notes' => 'Senior driver',
        'is_active' => true,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.company_id', $company->id)
        ->assertJsonPath('data.fleet_id', $fleet->id)
        ->assertJsonPath('data.employee_number', 'EMP-001')
        ->assertJsonPath('data.first_name', 'John')
        ->assertJsonPath('data.last_name', 'Doe');

    $this->assertDatabaseHas('drivers', [
        'company_id' => $company->id,
        'fleet_id' => $fleet->id,
        'employee_number' => 'EMP-001',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
});

test('company admin cannot create driver for another company', function (): void {

    $companyA = $this->createCompany();

    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $this->actingAsCompanyAdmin($companyA);

    $this->postJson('/api/v1/drivers', [
        'fleet_id' => $fleetB->id,
        'employee_number' => 'EMP-999',
        'first_name' => 'Illegal',
        'last_name' => 'Driver',
        'phone' => '+38591111111',
        'email' => 'illegal@example.com',
        'license_number' => 'LIC-999',
        'license_category' => 'C',
        'license_expiry_date' => now()->addYear()->toDateString(),
        'employment_date' => now()->subYear()->toDateString(),
    ]);

    $this->assertDatabaseMissing('drivers', [
        'company_id' => $companyB->id,
        'employee_number' => 'EMP-999',
    ]);
});

test('employee number is required', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->postJson('/api/v1/drivers', [
        'fleet_id' => $fleet->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'license_number' => 'LIC-001',
        'license_category' => 'C',
        'license_expiry_date' => now()->addYear()->toDateString(),
        'employment_date' => now()->subYear()->toDateString(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'employee_number',
        ]);
});

test('license number is required', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->postJson('/api/v1/drivers', [
        'fleet_id' => $fleet->id,
        'employee_number' => 'EMP-001',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'license_category' => 'C',
        'license_expiry_date' => now()->addYear()->toDateString(),
        'employment_date' => now()->subYear()->toDateString(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'license_number',
        ]);
});

test('company admin can update own driver', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $driver = $this->createDriver($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $response = $this->putJson("/api/v1/drivers/{$driver->id}", [
        'fleet_id' => $fleet->id,
        'employee_number' => 'EMP-999',
        'first_name' => 'Updated',
        'last_name' => 'Driver',
        'phone' => '+385987654321',
        'email' => 'updated@example.com',
        'license_number' => 'LIC-999',
        'license_category' => 'CE',
        'license_expiry_date' => now()->addYears(2)->toDateString(),
        'employment_date' => now()->subYears(2)->toDateString(),
        'notes' => 'Updated notes',
        'is_active' => false,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Updated')
        ->assertJsonPath('data.last_name', 'Driver')
        ->assertJsonPath('data.employee_number', 'EMP-999');

    $this->assertDatabaseHas('drivers', [
        'id' => $driver->id,
        'employee_number' => 'EMP-999',
        'first_name' => 'Updated',
        'last_name' => 'Driver',
        'is_active' => false,
    ]);
});

test('company admin cannot update driver from another company', function (): void {

    $companyA = $this->createCompany();

    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $driver = $this->createDriver($companyB, $fleetB);

    $this->actingAsCompanyAdmin($companyA);

    $this->putJson("/api/v1/drivers/{$driver->id}", [
        'fleet_id' => $fleetB->id,
        'employee_number' => 'EMP-HACK',
        'first_name' => 'Hacked',
        'last_name' => 'Driver',
        'license_number' => 'HACK-001',
        'license_category' => 'C',
        'license_expiry_date' => now()->addYear()->toDateString(),
        'employment_date' => now()->subYear()->toDateString(),
    ])
        ->assertForbidden();
});

test('company admin can delete own driver', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $driver = $this->createDriver($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $this->deleteJson("/api/v1/drivers/{$driver->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('drivers', [
        'id' => $driver->id,
    ]);
});

test('company admin cannot delete driver from another company', function (): void {

    $companyA = $this->createCompany();

    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $driver = $this->createDriver($companyB, $fleetB);

    $this->actingAsCompanyAdmin($companyA);

    $this->deleteJson("/api/v1/drivers/{$driver->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('drivers', [
        'id' => $driver->id,
    ]);
});

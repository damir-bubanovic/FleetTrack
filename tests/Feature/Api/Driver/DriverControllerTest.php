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
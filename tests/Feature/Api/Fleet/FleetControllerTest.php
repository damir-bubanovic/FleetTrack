<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesFleets;
use Tests\Traits\CreatesUsers;

uses(
    RefreshDatabase::class,
    CreatesUsers::class,
    CreatesCompanies::class,
    CreatesFleets::class,
);

test('super admin can list fleets', function (): void {

    $companyA = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $companyB = $this->createCompany([
        'name' => 'Company B',
        'slug' => 'company-b',
    ]);

    $this->createFleets($companyA, 3);
    $this->createFleets($companyB, 2);

    $user = $this->actingAsSuperAdmin();

    $response = $this->getJson('/api/v1/fleets');

    $response
        ->assertOk()
        ->assertJsonCount(5, 'data');
});


test('company admin only sees fleets from own company', function (): void {

    $companyA = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $companyB = $this->createCompany([
        'name' => 'Company B',
        'slug' => 'company-b',
    ]);

    $this->createFleets($companyA, 3);

    $this->createFleets($companyB, 2);

    $this->actingAsCompanyAdmin($companyA);

    $response = $this->getJson('/api/v1/fleets');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});



test('company admin can view own fleet', function (): void {

    $company = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $fleet = $this->createFleet($company);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson(
        "/api/v1/fleets/{$fleet->id}"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $fleet->id)
        ->assertJsonPath('data.company_id', $company->id);
});



test('company admin cannot view fleet from another company', function (): void {

    $companyA = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $companyB = $this->createCompany([
        'name' => 'Company B',
        'slug' => 'company-b',
    ]);

    $fleet = $this->createFleet($companyB);

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson("/api/v1/fleets/{$fleet->id}")
        ->assertForbidden();
});


test('company admin can create fleet', function (): void {

    $company = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $this->actingAsCompanyAdmin($company);

    $response = $this->postJson('/api/v1/fleets', [
        'name' => 'Operations Fleet',
        'code' => 'OPS',
        'description' => 'Operations vehicles',
        'is_active' => true,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Operations Fleet')
        ->assertJsonPath('data.company_id', $company->id);

    $this->assertDatabaseHas('fleets', [
        'company_id' => $company->id,
        'name' => 'Operations Fleet',
        'code' => 'OPS',
    ]);
});


test('company admin cannot create fleet for another company', function (): void {

    $companyA = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $companyB = $this->createCompany([
        'name' => 'Company B',
        'slug' => 'company-b',
    ]);

    $this->actingAsCompanyAdmin($companyA);

    $this->postJson('/api/v1/fleets', [
        'company_id' => $companyB->id,
        'name' => 'Illegal Fleet',
        'code' => 'BAD',
    ]);

    $this->assertDatabaseHas('fleets', [
        'company_id' => $companyA->id,
        'name' => 'Illegal Fleet',
        'code' => 'BAD',
    ]);

    $this->assertDatabaseMissing('fleets', [
        'company_id' => $companyB->id,
        'name' => 'Illegal Fleet',
    ]);
});



test('fleet name is required', function (): void {

    $company = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->postJson('/api/v1/fleets', [
        'code' => 'OPS',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
        ]);
});


test('company admin can update own fleet', function (): void {

    $company = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $fleet = $this->createFleet($company);

    $this->actingAsCompanyAdmin($company);

    $response = $this->putJson("/api/v1/fleets/{$fleet->id}", [
        'name' => 'Updated Fleet',
        'code' => 'UPD',
        'description' => 'Updated description',
        'is_active' => false,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Fleet')
        ->assertJsonPath('data.code', 'UPD');

    $this->assertDatabaseHas('fleets', [
        'id' => $fleet->id,
        'name' => 'Updated Fleet',
        'code' => 'UPD',
        'is_active' => false,
    ]);
});


test('company admin cannot update fleet from another company', function (): void {

    $companyA = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $companyB = $this->createCompany([
        'name' => 'Company B',
        'slug' => 'company-b',
    ]);

    $fleet = $this->createFleet($companyB);

    $this->actingAsCompanyAdmin($companyA);

    $this->putJson("/api/v1/fleets/{$fleet->id}", [
        'name' => 'Hacked',
        'code' => 'BAD',
    ])
        ->assertForbidden();
});


test('company admin can delete own fleet', function (): void {

    $company = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $fleet = $this->createFleet($company);

    $this->actingAsCompanyAdmin($company);

    $this->deleteJson("/api/v1/fleets/{$fleet->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('fleets', [
        'id' => $fleet->id,
    ]);
});

test('company admin cannot delete fleet from another company', function (): void {

    $companyA = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $companyB = $this->createCompany([
        'name' => 'Company B',
        'slug' => 'company-b',
    ]);

    $fleet = $this->createFleet($companyB);

    $this->actingAsCompanyAdmin($companyA);

    $this->deleteJson("/api/v1/fleets/{$fleet->id}")
        ->assertForbidden();
});
<?php

use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesUsers;

uses(
    RefreshDatabase::class,
    CreatesUsers::class,
    CreatesCompanies::class,
);

test('super admin can list companies', function (): void {

    $this->createCompanies(3);

    $this->actingAsSuperAdmin();

    $response = $this->getJson('/api/v1/companies');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('super admin can create company', function (): void {

    $this->actingAsSuperAdmin();

    $response = $this->postJson('/api/v1/companies', [
        'name' => 'FleetTrack Croatia',
        'slug' => 'fleettrack-croatia',
        'email' => 'info@fleettrack.hr',
        'phone' => '+385911234567',
        'address' => 'Savska cesta 1',
        'city' => 'Zagreb',
        'state' => 'Grad Zagreb',
        'postal_code' => '10000',
        'country' => 'Croatia',
        'is_active' => true,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'FleetTrack Croatia')
        ->assertJsonPath('data.slug', 'fleettrack-croatia');

    $company = Company::query()
        ->where('slug', 'fleettrack-croatia')
        ->first();

    expect($company)->not->toBeNull();

    expect($company->name)->toBe('FleetTrack Croatia');

    $roles = Role::query()
        ->where('company_id', $company->id)
        ->pluck('name')
        ->toArray();

    expect($roles)->toContain(
        UserRole::CompanyAdmin->value,
        UserRole::FleetManager->value,
        UserRole::Driver->value,
    );
});

test('super admin can view company', function (): void {

    $company = $this->createCompany();

    $this->actingAsSuperAdmin();

    $this->getJson("/api/v1/companies/{$company->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $company->id)
        ->assertJsonPath('data.name', $company->name);
});

test('super admin can update company', function (): void {

    $company = $this->createCompany();

    $this->actingAsSuperAdmin();

    $this->putJson("/api/v1/companies/{$company->id}", [
        'name' => 'Updated Company',
        'slug' => $company->slug,
        'email' => $company->email,
        'phone' => $company->phone,
        'address' => $company->address,
        'city' => $company->city,
        'state' => $company->state,
        'postal_code' => $company->postal_code,
        'country' => $company->country,
        'is_active' => false,
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Company')
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'name' => 'Updated Company',
        'is_active' => false,
    ]);
});

test('super admin can delete company', function (): void {

    $company = $this->createCompany();

    $this->actingAsSuperAdmin();

    $this->deleteJson("/api/v1/companies/{$company->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('companies', [
        'id' => $company->id,
    ]);
});
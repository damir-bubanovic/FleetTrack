<?php

declare(strict_types=1);

use App\Models\Geofence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesUsers;

uses(
    RefreshDatabase::class,
    CreatesCompanies::class,
    CreatesUsers::class,
);

it('allows a company admin to list geofences from their own company', function (): void {
    $company = $this->createCompany();
    $otherCompany = $this->createCompany();

    $user = $this->createCompanyAdmin($company);

    $ownGeofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $otherGeofence = Geofence::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson('/api/v1/geofences');

    $response
        ->assertOk()
        ->assertJsonFragment([
            'id' => $ownGeofence->id,
        ])
        ->assertJsonMissing([
            'id' => $otherGeofence->id,
        ]);
});

it('allows a company admin to view their own geofence', function (): void {
    $company = $this->createCompany();
    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $this
        ->actingAs($user)
        ->getJson("/api/v1/geofences/{$geofence->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $geofence->id)
        ->assertJsonPath('data.company_id', $company->id);
});

it('prevents a company admin from viewing another company geofence', function (): void {
    $company = $this->createCompany();
    $otherCompany = $this->createCompany();

    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->actingAs($user)
        ->getJson("/api/v1/geofences/{$geofence->id}")
        ->assertForbidden();
});

it('allows a company admin to create a geofence for their company', function (): void {
    $company = $this->createCompany();
    $user = $this->createCompanyAdmin($company);

    $response = $this
        ->actingAs($user)
        ->postJson('/api/v1/geofences', [
            'name' => 'Warehouse',
            'description' => 'Main warehouse',
            'area' => 'CIRCLE (45.8150 15.9819, 100)',
            'is_active' => true,
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Warehouse')
        ->assertJsonPath('data.company_id', $company->id);

    $this->assertDatabaseHas('geofences', [
        'company_id' => $company->id,
        'name' => 'Warehouse',
        'area' => 'CIRCLE (45.8150 15.9819, 100)',
        'is_active' => true,
    ]);
});

it('does not allow a company admin to create a geofence for another company', function (): void {
    $company = $this->createCompany();
    $otherCompany = $this->createCompany();

    $user = $this->createCompanyAdmin($company);

    $response = $this
        ->actingAs($user)
        ->postJson('/api/v1/geofences', [
            'company_id' => $otherCompany->id,
            'name' => 'Restricted Area',
            'area' => 'CIRCLE (45.8150 15.9819, 100)',
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.company_id', $company->id);

    $this->assertDatabaseHas('geofences', [
        'company_id' => $company->id,
        'name' => 'Restricted Area',
    ]);

    $this->assertDatabaseMissing('geofences', [
        'company_id' => $otherCompany->id,
        'name' => 'Restricted Area',
    ]);
});

it('validates required geofence fields', function (): void {
    $company = $this->createCompany();
    $user = $this->createCompanyAdmin($company);

    $this
        ->actingAs($user)
        ->postJson('/api/v1/geofences', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'area',
        ]);
});

it('does not allow clients to set traccar managed fields', function (): void {
    $company = $this->createCompany();
    $user = $this->createCompanyAdmin($company);

    $response = $this
        ->actingAs($user)
        ->postJson('/api/v1/geofences', [
            'name' => 'Office',
            'area' => 'CIRCLE (45.8150 15.9819, 100)',
            'traccar_geofence_id' => 123456,
            'last_sync_at' => now()->toISOString(),
        ]);

    $response->assertSuccessful();

    $geofence = Geofence::query()
        ->where('name', 'Office')
        ->firstOrFail();

    expect($geofence->traccar_geofence_id)->toBeNull()
        ->and($geofence->last_sync_at)->toBeNull();
});

it('allows a company admin to update their own geofence', function (): void {
    $company = $this->createCompany();
    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
        'name' => 'Old Name',
    ]);

    $this
        ->actingAs($user)
        ->patchJson("/api/v1/geofences/{$geofence->id}", [
            'name' => 'New Name',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('geofences', [
        'id' => $geofence->id,
        'company_id' => $company->id,
        'name' => 'New Name',
        'is_active' => false,
    ]);
});

it('prevents a company admin from updating another company geofence', function (): void {
    $company = $this->createCompany();
    $otherCompany = $this->createCompany();

    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->actingAs($user)
        ->patchJson("/api/v1/geofences/{$geofence->id}", [
            'name' => 'Changed',
        ])
        ->assertForbidden();
});

it('does not allow a company admin to transfer a geofence to another company', function (): void {
    $company = $this->createCompany();
    $otherCompany = $this->createCompany();

    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $this
        ->actingAs($user)
        ->patchJson("/api/v1/geofences/{$geofence->id}", [
            'company_id' => $otherCompany->id,
            'name' => 'Updated Geofence',
        ])
        ->assertOk()
        ->assertJsonPath('data.company_id', $company->id);

    expect($geofence->refresh()->company_id)->toBe($company->id);
});

it('allows a company admin to delete their own geofence', function (): void {
    $company = $this->createCompany();
    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson("/api/v1/geofences/{$geofence->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('geofences', [
        'id' => $geofence->id,
    ]);
});

it('prevents a company admin from deleting another company geofence', function (): void {
    $company = $this->createCompany();
    $otherCompany = $this->createCompany();

    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson("/api/v1/geofences/{$geofence->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('geofences', [
        'id' => $geofence->id,
    ]);
});
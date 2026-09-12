<?php

declare(strict_types=1);

use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesUsers;

uses(
    RefreshDatabase::class,
    CreatesCompanies::class,
    CreatesUsers::class,
);

beforeEach(function (): void {
    Queue::fake();
});

it('allows a company admin to attach their vehicle to a geofence', function (): void {
    $company = $this->createCompany();
    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $this
        ->actingAs($user)
        ->postJson(
            "/api/v1/geofences/{$geofence->id}/vehicles/{$vehicle->id}",
        )
        ->assertNoContent();

    $this->assertDatabaseHas('geofence_vehicle', [
        'geofence_id' => $geofence->id,
        'vehicle_id' => $vehicle->id,
    ]);
});

it('attaching the same vehicle twice is idempotent', function (): void {
    $company = $this->createCompany();
    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $url = "/api/v1/geofences/{$geofence->id}/vehicles/{$vehicle->id}";

    $this
        ->actingAs($user)
        ->postJson($url)
        ->assertNoContent();

    $this
        ->actingAs($user)
        ->postJson($url)
        ->assertNoContent();

    expect(
        $geofence->vehicles()
            ->whereKey($vehicle->id)
            ->count()
    )->toBe(1);
});

it('prevents a company admin from attaching another company vehicle', function (): void {
    $company = $this->createCompany();
    $otherCompany = $this->createCompany();

    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->actingAs($user)
        ->postJson(
            "/api/v1/geofences/{$geofence->id}/vehicles/{$vehicle->id}",
        )
        ->assertForbidden();

    $this->assertDatabaseMissing('geofence_vehicle', [
        'geofence_id' => $geofence->id,
        'vehicle_id' => $vehicle->id,
    ]);
});

it('allows a company admin to detach their vehicle from a geofence', function (): void {
    $company = $this->createCompany();
    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    $this
        ->actingAs($user)
        ->deleteJson(
            "/api/v1/geofences/{$geofence->id}/vehicles/{$vehicle->id}",
        )
        ->assertNoContent();

    $this->assertDatabaseMissing('geofence_vehicle', [
        'geofence_id' => $geofence->id,
        'vehicle_id' => $vehicle->id,
    ]);
});

it('detaching a vehicle that is not attached is idempotent', function (): void {
    $company = $this->createCompany();
    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson(
            "/api/v1/geofences/{$geofence->id}/vehicles/{$vehicle->id}",
        )
        ->assertNoContent();

    $this->assertDatabaseMissing('geofence_vehicle', [
        'geofence_id' => $geofence->id,
        'vehicle_id' => $vehicle->id,
    ]);
});

it('prevents a company admin from detaching another company vehicle', function (): void {
    $company = $this->createCompany();
    $otherCompany = $this->createCompany();

    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson(
            "/api/v1/geofences/{$geofence->id}/vehicles/{$vehicle->id}",
        )
        ->assertForbidden();
});

it('prevents another company from attaching a vehicle to a geofence it does not own', function (): void {
    $company = $this->createCompany();
    $otherCompany = $this->createCompany();

    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->actingAs($user)
        ->postJson(
            "/api/v1/geofences/{$geofence->id}/vehicles/{$vehicle->id}",
        )
        ->assertForbidden();

    $this->assertDatabaseMissing('geofence_vehicle', [
        'geofence_id' => $geofence->id,
        'vehicle_id' => $vehicle->id,
    ]);
});

it('prevents another company from detaching a vehicle from a geofence it does not own', function (): void {
    $company = $this->createCompany();
    $otherCompany = $this->createCompany();

    $user = $this->createCompanyAdmin($company);

    $geofence = Geofence::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    $this
        ->actingAs($user)
        ->deleteJson(
            "/api/v1/geofences/{$geofence->id}/vehicles/{$vehicle->id}",
        )
        ->assertForbidden();

    $this->assertDatabaseHas('geofence_vehicle', [
        'geofence_id' => $geofence->id,
        'vehicle_id' => $vehicle->id,
    ]);
});

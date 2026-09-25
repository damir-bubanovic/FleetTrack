<?php

declare(strict_types=1);

use App\Actions\Geofence\UpdateGeofence;
use App\Events\VehicleDetachedFromGeofence;
use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesUsers;

uses(
    RefreshDatabase::class,
    CreatesCompanies::class,
    CreatesUsers::class,
);

test('super admin changing geofence company removes vehicle assignments from previous company', function (): void {
    Event::fake([
        VehicleDetachedFromGeofence::class,
    ]);

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $vehicleA = Vehicle::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $geofence = Geofence::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $geofence->vehicles()->attach($vehicleA->id);

    $user = $this->actingAsSuperAdmin();

    $updatedGeofence = app(UpdateGeofence::class)->handle(
        $user,
        $geofence,
        [
            'company_id' => $companyB->id,
            'name' => $geofence->name,
        ],
    );

    expect($updatedGeofence->company_id)
        ->toBe($companyB->id);

    expect(
        $updatedGeofence->vehicles()->count(),
    )->toBe(0);

    $this->assertDatabaseMissing('geofence_vehicle', [
        'geofence_id' => $geofence->id,
        'vehicle_id' => $vehicleA->id,
    ]);

    Event::assertDispatched(
        VehicleDetachedFromGeofence::class,
        function (VehicleDetachedFromGeofence $event) use (
            $geofence,
            $vehicleA,
        ): bool {
            return $event->geofence->id === $geofence->id
                && $event->vehicle->id === $vehicleA->id;
        },
    );
});

test('updating geofence without changing company preserves vehicle assignments', function (): void {
    Event::fake([
        VehicleDetachedFromGeofence::class,
    ]);

    $company = $this->createCompany();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $geofence = Geofence::factory()->create([
        'company_id' => $company->id,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    $user = $this->actingAsSuperAdmin();

    $updatedGeofence = app(UpdateGeofence::class)->handle(
        $user,
        $geofence,
        [
            'company_id' => $company->id,
            'name' => 'Updated geofence',
        ],
    );

    expect($updatedGeofence->name)
        ->toBe('Updated geofence');

    expect($updatedGeofence->company_id)
        ->toBe($company->id);

    expect(
        $updatedGeofence->vehicles()
            ->whereKey($vehicle->id)
            ->exists(),
    )->toBeTrue();

    $this->assertDatabaseHas('geofence_vehicle', [
        'geofence_id' => $geofence->id,
        'vehicle_id' => $vehicle->id,
    ]);

    Event::assertNotDispatched(
        VehicleDetachedFromGeofence::class,
    );
});

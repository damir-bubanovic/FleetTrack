<?php

declare(strict_types=1);

use App\Actions\Vehicle\UpdateVehicle;
use App\Models\Device;
use App\Models\Fleet;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesUsers;

uses(
    RefreshDatabase::class,
    CreatesCompanies::class,
    CreatesUsers::class,
);

test('super admin moving vehicle to another company fleet keeps device ownership consistent', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $companyA->id,
        'fleet_id' => $fleetA->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $companyA->id,
        'vehicle_id' => $vehicle->id,
    ]);

    $user = $this->actingAsSuperAdmin();

    $updatedVehicle = app(UpdateVehicle::class)->handle(
        $user,
        $vehicle,
        [
            'fleet_id' => $fleetB->id,
            'registration_number' => $vehicle->registration_number,
            'vin' => $vehicle->vin,
            'manufacturer' => $vehicle->manufacturer,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'color' => $vehicle->color,
            'fuel_type' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission,
            'odometer' => $vehicle->odometer,
            'notes' => $vehicle->notes,
            'is_active' => $vehicle->is_active,
        ],
    );

    $device->refresh();

    expect($updatedVehicle->company_id)
        ->toBe($companyB->id);

    expect($updatedVehicle->fleet_id)
        ->toBe($fleetB->id);

    expect($device->company_id)
        ->toBe($companyB->id);

    expect($device->vehicle_id)
        ->toBe($vehicle->id);

    $this->assertDatabaseHas('devices', [
        'id' => $device->id,
        'company_id' => $companyB->id,
        'vehicle_id' => $vehicle->id,
    ]);
});

test('company admin cannot move vehicle to fleet in another company', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $companyA->id,
        'fleet_id' => $fleetA->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $companyA->id,
        'vehicle_id' => $vehicle->id,
    ]);

    $user = $this->actingAsCompanyAdmin($companyA);

    expect(
        fn () => app(UpdateVehicle::class)->handle(
            $user,
            $vehicle,
            [
                'fleet_id' => $fleetB->id,
            ],
        ),
    )->toThrow(
        AuthorizationException::class,
    );

    $vehicle->refresh();
    $device->refresh();

    expect($vehicle->company_id)
        ->toBe($companyA->id);

    expect($vehicle->fleet_id)
        ->toBe($fleetA->id);

    expect($device->company_id)
        ->toBe($companyA->id);
});

test('same company vehicle update preserves device ownership', function (): void {
    $company = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
        'fleet_id' => $fleetA->id,
    ]);

    $device = Device::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
    ]);

    $user = $this->actingAsCompanyAdmin($company);

    $updatedVehicle = app(UpdateVehicle::class)->handle(
        $user,
        $vehicle,
        [
            'fleet_id' => $fleetB->id,
        ],
    );

    $device->refresh();

    expect($updatedVehicle->company_id)
        ->toBe($company->id);

    expect($updatedVehicle->fleet_id)
        ->toBe($fleetB->id);

    expect($device->company_id)
        ->toBe($company->id);

    expect($device->vehicle_id)
        ->toBe($vehicle->id);
});

use App\Actions\Vehicle\CreateVehicle;

test('super admin can create vehicle in another company fleet', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $user = $this->actingAsSuperAdmin();

    $vehicle = app(CreateVehicle::class)->handle(
        $user,
        [
            'fleet_id' => $fleet->id,
            'registration_number' => 'ZG-1234-AB',
            'vin' => '1HGBH41JXMN109186',
            'manufacturer' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2025,
            'color' => 'Black',
            'fuel_type' => 'petrol',
            'transmission' => 'automatic',
            'odometer' => 0,
            'is_active' => true,
        ],
    );

    expect($vehicle->company_id)
        ->toBe($company->id);

    expect($vehicle->fleet_id)
        ->toBe($fleet->id);

    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'company_id' => $company->id,
        'fleet_id' => $fleet->id,
    ]);
});

test('company admin cannot create vehicle in another company fleet', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $user = $this->actingAsCompanyAdmin($companyA);

    expect(
        fn () => app(CreateVehicle::class)->handle(
            $user,
            [
                'fleet_id' => $fleetB->id,
                'registration_number' => 'ZG-9999-AB',
                'vin' => '1HGBH41JXMN109187',
                'manufacturer' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2025,
                'color' => 'Black',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'odometer' => 0,
                'is_active' => true,
            ],
        ),
    )->toThrow(
        AuthorizationException::class,
    );

    $this->assertDatabaseMissing('vehicles', [
        'fleet_id' => $fleetB->id,
        'registration_number' => 'ZG-9999-AB',
    ]);
});

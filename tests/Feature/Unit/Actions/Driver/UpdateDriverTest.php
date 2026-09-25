<?php

declare(strict_types=1);

use App\Actions\Driver\UpdateDriver;
use App\Models\Driver;
use App\Models\Fleet;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesUsers;

uses(
    RefreshDatabase::class,
    CreatesCompanies::class,
    CreatesUsers::class,
);

test('super admin can move driver to fleet in another company', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $driver = Driver::factory()->create([
        'company_id' => $companyA->id,
        'fleet_id' => $fleetA->id,
    ]);

    $user = $this->actingAsSuperAdmin();

    $updatedDriver = app(UpdateDriver::class)->handle(
        $user,
        $driver,
        [
            'company_id' => $companyB->id,
            'fleet_id' => $fleetB->id,
            'name' => $driver->name,
        ],
    );

    expect($updatedDriver->company_id)
        ->toBe($companyB->id);

    expect($updatedDriver->fleet_id)
        ->toBe($fleetB->id);

    $this->assertDatabaseHas('drivers', [
        'id' => $driver->id,
        'company_id' => $companyB->id,
        'fleet_id' => $fleetB->id,
    ]);
});

test('super admin cannot move driver to company that does not own selected fleet', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $driver = Driver::factory()->create([
        'company_id' => $companyA->id,
        'fleet_id' => $fleetA->id,
    ]);

    $user = $this->actingAsSuperAdmin();

    expect(
        fn () => app(UpdateDriver::class)->handle(
            $user,
            $driver,
            [
                'company_id' => $companyB->id,
                'fleet_id' => $fleetA->id,
                'name' => $driver->name,
            ],
        ),
    )->toThrow(ModelNotFoundException::class);

    $driver->refresh();

    expect($driver->company_id)
        ->toBe($companyA->id);

    expect($driver->fleet_id)
        ->toBe($fleetA->id);
});

test('company admin cannot move driver to another company fleet', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $driver = Driver::factory()->create([
        'company_id' => $companyA->id,
        'fleet_id' => $fleetA->id,
    ]);

    $user = $this->actingAsCompanyAdmin($companyA);

    expect(
        fn () => app(UpdateDriver::class)->handle(
            $user,
            $driver,
            [
                'company_id' => $companyB->id,
                'fleet_id' => $fleetB->id,
                'name' => $driver->name,
            ],
        ),
    )->toThrow(ModelNotFoundException::class);

    $driver->refresh();

    expect($driver->company_id)
        ->toBe($companyA->id);

    expect($driver->fleet_id)
        ->toBe($fleetA->id);
});

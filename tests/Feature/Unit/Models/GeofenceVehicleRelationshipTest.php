<?php

declare(strict_types=1);

use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a geofence can have vehicles', function (): void {
    $geofence = Geofence::factory()->create();

    $vehicles = Vehicle::factory()
        ->count(2)
        ->create([
            'company_id' => $geofence->company_id,
        ]);

    $geofence->vehicles()->attach($vehicles->modelKeys());

    expect($geofence->vehicles)
        ->toHaveCount(2)
        ->and($geofence->vehicles->modelKeys())
        ->toEqualCanonicalizing($vehicles->modelKeys());
});

test('a vehicle can have geofences', function (): void {
    $vehicle = Vehicle::factory()->create();

    $geofences = Geofence::factory()
        ->count(2)
        ->create([
            'company_id' => $vehicle->company_id,
        ]);

    $vehicle->geofences()->attach($geofences->modelKeys());

    expect($vehicle->geofences)
        ->toHaveCount(2)
        ->and($vehicle->geofences->modelKeys())
        ->toEqualCanonicalizing($geofences->modelKeys());
});

test('the same vehicle cannot be attached to a geofence twice', function (): void {
    $geofence = Geofence::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $geofence->company_id,
    ]);

    $geofence->vehicles()->attach($vehicle->id);

    expect(
        fn () => $geofence->vehicles()->attach($vehicle->id)
    )->toThrow(QueryException::class);
});

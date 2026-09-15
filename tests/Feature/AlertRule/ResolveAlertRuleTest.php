<?php

declare(strict_types=1);

use App\Actions\AlertRule\ResolveAlertRule;
use App\Models\AlertRule;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves an active company wide rule', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'type' => 'device_offline',
        'is_active' => true,
        'conditions' => [],
    ]);

    $resolved = app(ResolveAlertRule::class)->execute(
        $vehicle,
        'device_offline',
    );

    expect($resolved?->is($rule))->toBeTrue();
});

it('prefers a vehicle specific rule over a company wide rule', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'type' => 'device_offline',
        'severity' => 'warning',
        'conditions' => [],
    ]);

    $vehicleRule = AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'type' => 'device_offline',
        'severity' => 'critical',
        'conditions' => [],
    ]);

    $resolved = app(ResolveAlertRule::class)->execute(
        $vehicle,
        'device_offline',
    );

    expect($resolved?->is($vehicleRule))->toBeTrue()
        ->and($resolved?->severity)->toBe('critical');
});

it('does not resolve an inactive rule', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'type' => 'device_offline',
        'is_active' => false,
        'conditions' => [],
    ]);

    $resolved = app(ResolveAlertRule::class)->execute(
        $vehicle,
        'device_offline',
    );

    expect($resolved)->toBeNull();
});

it('does not resolve a rule belonging to another company', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    AlertRule::factory()->create([
        'company_id' => $otherCompany->id,
        'vehicle_id' => null,
        'type' => 'device_offline',
        'conditions' => [],
    ]);

    $resolved = app(ResolveAlertRule::class)->execute(
        $vehicle,
        'device_offline',
    );

    expect($resolved)->toBeNull();
});

it('does not resolve a rule for a different event type', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'type' => 'ignition_on',
        'conditions' => [],
    ]);

    $resolved = app(ResolveAlertRule::class)->execute(
        $vehicle,
        'device_offline',
    );

    expect($resolved)->toBeNull();
});

it('resolves an overspeed rule when the configured speed limit is exceeded', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'type' => 'overspeed',
        'conditions' => [
            'speed_limit_kmh' => 90,
        ],
    ]);

    $resolved = app(ResolveAlertRule::class)->execute(
        $vehicle,
        'overspeed',
        105.0,
    );

    expect($resolved?->is($rule))->toBeTrue();
});

it('does not resolve an overspeed rule when the configured speed limit is not exceeded', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'type' => 'overspeed',
        'conditions' => [
            'speed_limit_kmh' => 90,
        ],
    ]);

    $resolved = app(ResolveAlertRule::class)->execute(
        $vehicle,
        'overspeed',
        90.0,
    );

    expect($resolved)->toBeNull();
});

it('falls back to a matching company wide overspeed rule', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'type' => 'overspeed',
        'conditions' => [
            'speed_limit_kmh' => 120,
        ],
    ]);

    $companyRule = AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
        'type' => 'overspeed',
        'conditions' => [
            'speed_limit_kmh' => 90,
        ],
    ]);

    $resolved = app(ResolveAlertRule::class)->execute(
        $vehicle,
        'overspeed',
        100.0,
    );

    expect($resolved?->is($companyRule))->toBeTrue();
});

<?php

declare(strict_types=1);

use App\Models\AlertRule;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('casts conditions and active state correctly', function (): void {
    $rule = AlertRule::factory()->create([
        'conditions' => [
            'speed_limit_kmh' => 100,
        ],
        'is_active' => false,
    ]);

    $rule->refresh();

    expect($rule->conditions)
        ->toBe([
            'speed_limit_kmh' => 100,
        ])
        ->and($rule->is_active)
        ->toBeFalse();
});

it('belongs to a company', function (): void {
    $company = Company::factory()->create();

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    expect($rule->company->is($company))->toBeTrue();
});

it('may belong to a vehicle', function (): void {
    $company = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
    ]);

    expect($rule->vehicle)
        ->not->toBeNull()
        ->and($rule->vehicle->is($vehicle))
        ->toBeTrue();
});

it('may be a company wide rule without a vehicle', function (): void {
    $rule = AlertRule::factory()->create([
        'vehicle_id' => null,
    ]);

    expect($rule->vehicle)->toBeNull();
});

it('only exposes rules belonging to the users company', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $ownRule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    AlertRule::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $rules = AlertRule::query()
        ->visibleTo($user)
        ->get();

    expect($rules)
        ->toHaveCount(1)
        ->and($rules->first()->is($ownRule))
        ->toBeTrue();
});

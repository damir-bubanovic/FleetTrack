<?php

declare(strict_types=1);

use App\Actions\AlertRule\CreateAlertRule;
use App\Actions\Company\ProvisionCompanyRoles;
use App\Enums\UserRole;
use App\Models\AlertRule;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createAlertRuleActionCompanyAdmin(Company $company): User
{
    app(ProvisionCompanyRoles::class)->handle($company);

    setPermissionsTeamId($company->id);

    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $role = Role::findByName(
        UserRole::CompanyAdmin->value,
        'web',
    );

    $user->assignRole($role);

    return $user;
}

it('creates a company wide alert rule for the users company', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleActionCompanyAdmin($company);

    $rule = app(CreateAlertRule::class)->execute($user, [
        'name' => 'Company speed rule',
        'type' => 'overspeed',
        'severity' => 'warning',
        'conditions' => [
            'speed_limit_kmh' => 90,
        ],
        'is_active' => true,
    ]);

    expect($rule)
        ->toBeInstanceOf(AlertRule::class)
        ->and($rule->company_id)->toBe($company->id)
        ->and($rule->vehicle_id)->toBeNull()
        ->and($rule->name)->toBe('Company speed rule')
        ->and($rule->type)->toBe('overspeed')
        ->and($rule->severity)->toBe('warning')
        ->and($rule->conditions)->toBe([
            'speed_limit_kmh' => 90,
        ])
        ->and($rule->is_active)->toBeTrue();
});

it('creates a vehicle specific alert rule', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleActionCompanyAdmin($company);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $rule = app(CreateAlertRule::class)->execute($user, [
        'vehicle_id' => $vehicle->id,
        'name' => 'Vehicle speed rule',
        'type' => 'overspeed',
        'severity' => 'critical',
        'conditions' => [
            'speed_limit_kmh' => 110,
        ],
        'is_active' => true,
    ]);

    expect($rule->company_id)
        ->toBe($company->id)
        ->and($rule->vehicle_id)
        ->toBe($vehicle->id);
});

it('ignores a forged company id for a company user', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleActionCompanyAdmin($company);

    $rule = app(CreateAlertRule::class)->execute($user, [
        'company_id' => $otherCompany->id,
        'name' => 'Forged company rule',
        'type' => 'overspeed',
        'severity' => 'warning',
        'conditions' => [
            'speed_limit_kmh' => 90,
        ],
        'is_active' => true,
    ]);

    expect($rule->company_id)->toBe($company->id);

    $this->assertDatabaseHas('alert_rules', [
        'id' => $rule->id,
        'company_id' => $company->id,
    ]);

    $this->assertDatabaseMissing('alert_rules', [
        'id' => $rule->id,
        'company_id' => $otherCompany->id,
    ]);
});

it('rejects a vehicle belonging to another company', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleActionCompanyAdmin($company);

    $otherVehicle = Vehicle::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    expect(
        fn () => app(CreateAlertRule::class)->execute($user, [
            'vehicle_id' => $otherVehicle->id,
            'name' => 'Invalid vehicle rule',
            'type' => 'overspeed',
            'severity' => 'warning',
            'conditions' => [
                'speed_limit_kmh' => 90,
            ],
            'is_active' => true,
        ]),
    )->toThrow(ModelNotFoundException::class);

    expect(AlertRule::query()->count())->toBe(0);
});

it('rejects a company user without a company', function (): void {
    $user = User::factory()->create([
        'company_id' => null,
    ]);

    expect(
        fn () => app(CreateAlertRule::class)->execute($user, [
            'name' => 'Invalid rule',
            'type' => 'overspeed',
            'severity' => 'warning',
            'conditions' => [
                'speed_limit_kmh' => 90,
            ],
            'is_active' => true,
        ]),
    )->toThrow(
        RuntimeException::class,
        'User is not assigned to a company.',
    );

    expect(AlertRule::query()->count())->toBe(0);
});

<?php

declare(strict_types=1);

use App\Actions\AlertRule\UpdateAlertRule;
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

function createUpdateAlertRuleCompanyAdmin(Company $company): User
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

it('updates an alert rule', function (): void {
    $company = Company::factory()->create();

    $user = createUpdateAlertRuleCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
        'name' => 'Old rule',
        'severity' => 'warning',
        'conditions' => [
            'speed_limit_kmh' => 90,
        ],
        'is_active' => true,
    ]);

    $updatedRule = app(UpdateAlertRule::class)->execute(
        $user,
        $rule,
        [
            'name' => 'Updated rule',
            'severity' => 'critical',
            'conditions' => [
                'speed_limit_kmh' => 110,
            ],
            'is_active' => false,
        ],
    );

    expect($updatedRule->name)
        ->toBe('Updated rule')
        ->and($updatedRule->severity)
        ->toBe('critical')
        ->and($updatedRule->conditions)
        ->toBe([
            'speed_limit_kmh' => 110,
        ])
        ->and($updatedRule->is_active)
        ->toBeFalse();
});

it('changes the vehicle within the same company', function (): void {
    $company = Company::factory()->create();

    $user = createUpdateAlertRuleCompanyAdmin($company);

    $firstVehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $secondVehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $firstVehicle->id,
    ]);

    $updatedRule = app(UpdateAlertRule::class)->execute(
        $user,
        $rule,
        [
            'vehicle_id' => $secondVehicle->id,
        ],
    );

    expect($updatedRule->vehicle_id)
        ->toBe($secondVehicle->id);
});

it('can convert a vehicle rule to a company wide rule', function (): void {
    $company = Company::factory()->create();

    $user = createUpdateAlertRuleCompanyAdmin($company);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
    ]);

    $updatedRule = app(UpdateAlertRule::class)->execute(
        $user,
        $rule,
        [
            'vehicle_id' => null,
        ],
    );

    expect($updatedRule->vehicle_id)->toBeNull();
});

it('ignores a company transfer attempted by a company user', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createUpdateAlertRuleCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    $updatedRule = app(UpdateAlertRule::class)->execute(
        $user,
        $rule,
        [
            'company_id' => $otherCompany->id,
            'name' => 'Still our rule',
        ],
    );

    expect($updatedRule->company_id)
        ->toBe($company->id)
        ->and($updatedRule->name)
        ->toBe('Still our rule');
});

it('rejects a vehicle belonging to another company', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createUpdateAlertRuleCompanyAdmin($company);

    $otherVehicle = Vehicle::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => null,
    ]);

    expect(
        fn () => app(UpdateAlertRule::class)->execute(
            $user,
            $rule,
            [
                'vehicle_id' => $otherVehicle->id,
            ],
        ),
    )->toThrow(ModelNotFoundException::class);

    expect($rule->fresh()->vehicle_id)->toBeNull();
});

<?php

declare(strict_types=1);

use App\Actions\Company\ProvisionCompanyRoles;
use App\Enums\UserRole;
use App\Models\AlertRule;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createAlertRuleCompanyAdmin(Company $company): User
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

it('allows a company admin to view alert rules', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleCompanyAdmin($company);

    expect($user->can('viewAny', AlertRule::class))
        ->toBeTrue();
});

it('allows a company admin to view an own company alert rule', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    expect($user->can('view', $rule))->toBeTrue();
});

it('forbids a company admin from viewing another company alert rule', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    expect($user->can('view', $rule))->toBeFalse();
});

it('allows a company admin to create alert rules', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleCompanyAdmin($company);

    expect($user->can('create', AlertRule::class))
        ->toBeTrue();
});

it('allows a company admin to update an own company alert rule', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    expect($user->can('update', $rule))->toBeTrue();
});

it('forbids a company admin from updating another company alert rule', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    expect($user->can('update', $rule))->toBeFalse();
});

it('allows a company admin to delete an own company alert rule', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    expect($user->can('delete', $rule))->toBeTrue();
});

it('forbids a company admin from deleting another company alert rule', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    expect($user->can('delete', $rule))->toBeFalse();
});

it('forbids a user without alert rule permissions', function (): void {
    $company = Company::factory()->create();

    setPermissionsTeamId($company->id);

    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    expect($user->can('viewAny', AlertRule::class))
        ->toBeFalse()
        ->and($user->can('view', $rule))
        ->toBeFalse()
        ->and($user->can('create', AlertRule::class))
        ->toBeFalse()
        ->and($user->can('update', $rule))
        ->toBeFalse()
        ->and($user->can('delete', $rule))
        ->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Actions\Company\ProvisionCompanyRoles;
use App\Enums\UserRole;
use App\Models\AlertRule;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Traits\CreatesUsers;

uses(
    RefreshDatabase::class,
    CreatesUsers::class,
);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createAlertRuleApiCompanyAdmin(Company $company): User
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

it('lists only alert rules belonging to the users company', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $ownRule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    $otherRule = AlertRule::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson('/api/v1/alert-rules')
        ->assertOk();

    $response
        ->assertJsonFragment([
            'id' => $ownRule->id,
        ])
        ->assertJsonMissing([
            'id' => $otherRule->id,
        ]);
});

it('shows an alert rule belonging to the users company', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    $this
        ->actingAs($user)
        ->getJson("/api/v1/alert-rules/{$rule->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $rule->id)
        ->assertJsonPath(
            'data.company_id',
            $company->id,
        );
});

it('forbids viewing another company alert rule', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->actingAs($user)
        ->getJson("/api/v1/alert-rules/{$rule->id}")
        ->assertForbidden();
});

it('creates a company wide alert rule', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $response = $this
        ->actingAs($user)
        ->postJson('/api/v1/alert-rules', [
            'name' => 'Company speed rule',
            'type' => 'overspeed',
            'severity' => 'warning',
            'conditions' => [
                'speed_limit_kmh' => 90,
            ],
            'is_active' => true,
        ])
        ->assertSuccessful();

    $response
        ->assertJsonPath('data.company_id', $company->id)
        ->assertJsonPath('data.vehicle_id', null)
        ->assertJsonPath('data.type', 'overspeed')
        ->assertJsonPath('data.severity', 'warning')
        ->assertJsonPath(
            'data.conditions.speed_limit_kmh',
            90,
        );

    $this->assertDatabaseHas('alert_rules', [
        'company_id' => $company->id,
        'vehicle_id' => null,
        'name' => 'Company speed rule',
        'type' => 'overspeed',
        'severity' => 'warning',
        'is_active' => true,
    ]);
});

it('creates a vehicle specific alert rule', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
    ]);

    $this
        ->actingAs($user)
        ->postJson('/api/v1/alert-rules', [
            'vehicle_id' => $vehicle->id,
            'name' => 'Vehicle speed rule',
            'type' => 'overspeed',
            'severity' => 'critical',
            'conditions' => [
                'speed_limit_kmh' => 110,
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath(
            'data.vehicle_id',
            $vehicle->id,
        );
});

it('does not allow a company user to create a rule for another company', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $this
        ->actingAs($user)
        ->postJson('/api/v1/alert-rules', [
            'company_id' => $otherCompany->id,
            'name' => 'Forged company rule',
            'type' => 'device_offline',
            'severity' => 'warning',
            'conditions' => [],
        ])
        ->assertSuccessful()
        ->assertJsonPath(
            'data.company_id',
            $company->id,
        );
});

it('rejects a vehicle belonging to another company', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->actingAs($user)
        ->postJson('/api/v1/alert-rules', [
            'vehicle_id' => $vehicle->id,
            'name' => 'Invalid vehicle rule',
            'type' => 'device_offline',
            'severity' => 'warning',
            'conditions' => [],
        ])
        ->assertNotFound();

    expect(AlertRule::query()->count())->toBe(0);
});

it('updates an alert rule belonging to the users company', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
        'name' => 'Old name',
    ]);

    $this
        ->actingAs($user)
        ->patchJson(
            "/api/v1/alert-rules/{$rule->id}",
            [
                'name' => 'Updated name',
                'severity' => 'critical',
                'is_active' => false,
            ],
        )
        ->assertOk()
        ->assertJsonPath(
            'data.name',
            'Updated name',
        )
        ->assertJsonPath(
            'data.severity',
            'critical',
        )
        ->assertJsonPath(
            'data.is_active',
            false,
        );

    $this->assertDatabaseHas('alert_rules', [
        'id' => $rule->id,
        'company_id' => $company->id,
        'name' => 'Updated name',
        'severity' => 'critical',
        'is_active' => false,
    ]);
});

it('forbids updating another company alert rule', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->actingAs($user)
        ->patchJson(
            "/api/v1/alert-rules/{$rule->id}",
            [
                'name' => 'Not allowed',
            ],
        )
        ->assertForbidden();
});

it('does not allow a company user to transfer an alert rule', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    $this
        ->actingAs($user)
        ->patchJson(
            "/api/v1/alert-rules/{$rule->id}",
            [
                'company_id' => $otherCompany->id,
            ],
        )
        ->assertOk()
        ->assertJsonPath(
            'data.company_id',
            $company->id,
        );

    expect($rule->fresh()->company_id)
        ->toBe($company->id);
});

it('deletes an alert rule belonging to the users company', function (): void {
    $company = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $company->id,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson("/api/v1/alert-rules/{$rule->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('alert_rules', [
        'id' => $rule->id,
    ]);
});

it('forbids deleting another company alert rule', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertRuleApiCompanyAdmin($company);

    $rule = AlertRule::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson("/api/v1/alert-rules/{$rule->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('alert_rules', [
        'id' => $rule->id,
    ]);
});

it('super admin changing alert rule company clears incompatible vehicle', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $vehicle = Vehicle::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $rule = AlertRule::factory()->create([
        'company_id' => $companyA->id,
        'vehicle_id' => $vehicle->id,
    ]);

    $this->actingAsSuperAdmin();

    $this
        ->patchJson("/api/v1/alert-rules/{$rule->id}", [
            'company_id' => $companyB->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.company_id', $companyB->id)
        ->assertJsonPath('data.vehicle_id', null);

    $this->assertDatabaseHas('alert_rules', [
        'id' => $rule->id,
        'company_id' => $companyB->id,
        'vehicle_id' => null,
    ]);
});

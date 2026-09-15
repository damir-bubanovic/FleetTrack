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

/**
 * @property Company $company
 * @property User $user
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->company = Company::factory()->create();

    app(ProvisionCompanyRoles::class)->handle($this->company);

    setPermissionsTeamId($this->company->id);

    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $role = Role::findByName(
        UserRole::CompanyAdmin->value,
        'web',
    );

    $this->user->assignRole($role);
});

it('requires the base fields when creating an alert rule', function (): void {
    $this
        ->actingAs($this->user)
        ->postJson('/api/v1/alert-rules', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'type',
            'severity',
            'conditions',
        ]);
});

it('requires a speed limit for an overspeed rule', function (): void {
    $this
        ->actingAs($this->user)
        ->postJson('/api/v1/alert-rules', [
            'name' => 'Speed rule',
            'type' => 'overspeed',
            'severity' => 'warning',
            'conditions' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'conditions.speed_limit_kmh',
        ]);
});

it('requires the overspeed speed limit to be greater than zero', function (): void {
    $this
        ->actingAs($this->user)
        ->postJson('/api/v1/alert-rules', [
            'name' => 'Speed rule',
            'type' => 'overspeed',
            'severity' => 'warning',
            'conditions' => [
                'speed_limit_kmh' => 0,
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'conditions.speed_limit_kmh',
        ]);
});

it('accepts a valid overspeed rule', function (): void {
    $this
        ->actingAs($this->user)
        ->postJson('/api/v1/alert-rules', [
            'name' => 'Speed rule',
            'type' => 'overspeed',
            'severity' => 'warning',
            'conditions' => [
                'speed_limit_kmh' => 90,
            ],
            'is_active' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.type', 'overspeed')
        ->assertJsonPath(
            'data.conditions.speed_limit_kmh',
            90,
        );
});

it('allows an overspeed rule to be updated without replacing conditions', function (): void {
    $rule = AlertRule::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'overspeed',
        'conditions' => [
            'speed_limit_kmh' => 90,
        ],
    ]);

    $this
        ->actingAs($this->user)
        ->patchJson(
            "/api/v1/alert-rules/{$rule->id}",
            [
                'name' => 'Updated speed rule',
            ],
        )
        ->assertSuccessful()
        ->assertJsonPath(
            'data.name',
            'Updated speed rule',
        );
});

it('requires a speed limit when replacing conditions on an existing overspeed rule', function (): void {
    $rule = AlertRule::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'overspeed',
        'conditions' => [
            'speed_limit_kmh' => 90,
        ],
    ]);

    $this
        ->actingAs($this->user)
        ->patchJson(
            "/api/v1/alert-rules/{$rule->id}",
            [
                'conditions' => [],
            ],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'conditions.speed_limit_kmh',
        ]);
});

it('rejects unsupported alert rule types', function (): void {
    $this
        ->actingAs($this->user)
        ->postJson('/api/v1/alert-rules', [
            'name' => 'Invalid rule',
            'type' => 'something_invalid',
            'severity' => 'warning',
            'conditions' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'type',
        ]);
});

it('rejects unsupported severity values', function (): void {
    $this
        ->actingAs($this->user)
        ->postJson('/api/v1/alert-rules', [
            'name' => 'Invalid severity',
            'type' => 'device_offline',
            'severity' => 'extreme',
            'conditions' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'severity',
        ]);
});

it('requires a speed limit when changing a rule to overspeed', function (): void {
    $rule = AlertRule::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'device_offline',
        'conditions' => [],
    ]);

    $this
        ->actingAs($this->user)
        ->patchJson(
            "/api/v1/alert-rules/{$rule->id}",
            [
                'type' => 'overspeed',
            ],
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'conditions.speed_limit_kmh',
        ]);
});

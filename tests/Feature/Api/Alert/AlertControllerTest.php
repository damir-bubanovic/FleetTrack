<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createAlertCompanyAdmin(Company $company): User
{
    setPermissionsTeamId($company->id);

    $role = Role::findOrCreate(
        'company admin',
        'web',
    );

    $role->givePermissionTo([
        'alerts.view',
        'alerts.acknowledge',
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $user->assignRole($role);

    return $user;
}

it('rejects unauthenticated access to alerts', function (): void {
    $this->getJson('/api/v1/alerts')
        ->assertUnauthorized();
});

it('allows a company admin to list alerts for their company', function (): void {
    $company = Company::factory()->create();
    $user = createAlertCompanyAdmin($company);

    $ownAlert = Alert::factory()->create([
        'company_id' => $company->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/alerts');

    $response
        ->assertOk()
        ->assertJsonPath('data.0.id', $ownAlert->id)
        ->assertJsonPath('data.0.company_id', $company->id);
});

it('does not list alerts belonging to another company', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertCompanyAdmin($company);

    $ownAlert = Alert::factory()->create([
        'company_id' => $company->id,
        'occurred_at' => now(),
    ]);

    $otherAlert = Alert::factory()->create([
        'company_id' => $otherCompany->id,
        'occurred_at' => now()->addMinute(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/alerts');

    $response
        ->assertOk()
        ->assertJsonFragment([
            'id' => $ownAlert->id,
        ])
        ->assertJsonMissing([
            'id' => $otherAlert->id,
        ]);
});

it('allows a company admin to view an alert for their company', function (): void {
    $company = Company::factory()->create();
    $user = createAlertCompanyAdmin($company);

    $alert = Alert::factory()->create([
        'company_id' => $company->id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/alerts/{$alert->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $alert->id)
        ->assertJsonPath('data.company_id', $company->id)
        ->assertJsonPath('data.type', $alert->type)
        ->assertJsonPath('data.severity', $alert->severity);
});

it('forbids a company admin from viewing another company alert', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertCompanyAdmin($company);

    $alert = Alert::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/alerts/{$alert->id}")
        ->assertForbidden();
});

it('allows a company admin to acknowledge an alert for their company', function (): void {
    Carbon::setTestNow('2026-09-13 10:30:00');

    $company = Company::factory()->create();
    $user = createAlertCompanyAdmin($company);

    $alert = Alert::factory()->create([
        'company_id' => $company->id,
        'acknowledged_at' => null,
        'acknowledged_by' => null,
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson(
        "/api/v1/alerts/{$alert->id}/acknowledge"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $alert->id)
        ->assertJsonPath('data.acknowledged_by', $user->id);

    $alert->refresh();

    expect($alert->acknowledged_at)
        ->not->toBeNull()
        ->and($alert->acknowledged_at->toDateTimeString())
        ->toBe('2026-09-13 10:30:00')
        ->and($alert->acknowledged_by)
        ->toBe($user->id);

    Carbon::setTestNow();
});

it('forbids a company admin from acknowledging another company alert', function (): void {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = createAlertCompanyAdmin($company);

    $alert = Alert::factory()->create([
        'company_id' => $otherCompany->id,
        'acknowledged_at' => null,
        'acknowledged_by' => null,
    ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/v1/alerts/{$alert->id}/acknowledge")
        ->assertForbidden();

    $alert->refresh();

    expect($alert->acknowledged_at)
        ->toBeNull()
        ->and($alert->acknowledged_by)
        ->toBeNull();
});

it('does not replace an existing alert acknowledgement', function (): void {
    $company = Company::factory()->create();

    $originalUser = createAlertCompanyAdmin($company);
    $secondUser = createAlertCompanyAdmin($company);

    $alert = Alert::factory()->create([
        'company_id' => $company->id,
        'acknowledged_at' => Carbon::parse('2026-09-12 08:15:00'),
        'acknowledged_by' => $originalUser->id,
    ]);

    Carbon::setTestNow('2026-09-13 10:30:00');

    Sanctum::actingAs($secondUser);

    $this->patchJson("/api/v1/alerts/{$alert->id}/acknowledge")
        ->assertOk()
        ->assertJsonPath(
            'data.acknowledged_by',
            $originalUser->id,
        );

    $alert->refresh();

    expect($alert->acknowledged_at->toDateTimeString())
        ->toBe('2026-09-12 08:15:00')
        ->and($alert->acknowledged_by)
        ->toBe($originalUser->id);

    Carbon::setTestNow();
});

it('forbids a user without alert permissions from viewing alerts', function (): void {
    $company = Company::factory()->create();

    setPermissionsTeamId($company->id);

    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/alerts')
        ->assertForbidden();
});

it('forbids a user without acknowledge permission from acknowledging an alert', function (): void {
    $company = Company::factory()->create();

    setPermissionsTeamId($company->id);

    $role = Role::findOrCreate(
        'alert viewer',
        'web',
    );

    $role->givePermissionTo('alerts.view');

    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $user->assignRole($role);

    $alert = Alert::factory()->create([
        'company_id' => $company->id,
        'acknowledged_at' => null,
        'acknowledged_by' => null,
    ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/v1/alerts/{$alert->id}/acknowledge")
        ->assertForbidden();

    $alert->refresh();

    expect($alert->acknowledged_at)
        ->toBeNull()
        ->and($alert->acknowledged_by)
        ->toBeNull();
});

it('allows a super admin to acknowledge an alert from another company', function (): void {
    Carbon::setTestNow('2026-09-13 10:30:00');

    $systemCompany = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    setPermissionsTeamId($systemCompany->id);

    $role = Role::findOrCreate(
        UserRole::SuperAdmin->value,
        'web',
    );

    $role->givePermissionTo([
        'alerts.view',
        'alerts.acknowledge',
    ]);

    $user = User::factory()->create([
        'company_id' => $systemCompany->id,
    ]);

    $user->assignRole($role);

    $alert = Alert::factory()->create([
        'company_id' => $otherCompany->id,
        'acknowledged_at' => null,
        'acknowledged_by' => null,
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson(
        "/api/v1/alerts/{$alert->id}/acknowledge"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $alert->id)
        ->assertJsonPath('data.acknowledged_by', $user->id);

    $alert->refresh();

    expect($alert->acknowledged_at)
        ->not->toBeNull()
        ->and($alert->acknowledged_at->toDateTimeString())
        ->toBe('2026-09-13 10:30:00')
        ->and($alert->acknowledged_by)
        ->toBe($user->id);

    Carbon::setTestNow();
});

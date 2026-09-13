<?php

declare(strict_types=1);

use App\Actions\Alert\AcknowledgeAlert;
use App\Models\Alert;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('acknowledges an alert', function (): void {
    Carbon::setTestNow('2026-09-13 10:30:00');

    $company = Company::factory()->create();

    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $alert = Alert::factory()->create([
        'company_id' => $company->id,
        'acknowledged_at' => null,
        'acknowledged_by' => null,
    ]);

    $result = app(AcknowledgeAlert::class)->execute(
        alert: $alert,
        user: $user,
    );

    expect($result->acknowledged_at)
        ->not->toBeNull()
        ->and($result->acknowledged_at->toDateTimeString())
        ->toBe('2026-09-13 10:30:00')
        ->and($result->acknowledged_by)
        ->toBe($user->id);

    $this->assertDatabaseHas('alerts', [
        'id' => $alert->id,
        'acknowledged_by' => $user->id,
        'acknowledged_at' => '2026-09-13 10:30:00',
    ]);

    Carbon::setTestNow();
});

it('does not change an already acknowledged alert', function (): void {
    $company = Company::factory()->create();

    $originalUser = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $secondUser = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $originalAcknowledgedAt = Carbon::parse('2026-09-12 08:15:00');

    $alert = Alert::factory()->create([
        'company_id' => $company->id,
        'acknowledged_at' => $originalAcknowledgedAt,
        'acknowledged_by' => $originalUser->id,
    ]);

    Carbon::setTestNow('2026-09-13 10:30:00');

    $result = app(AcknowledgeAlert::class)->execute(
        alert: $alert,
        user: $secondUser,
    );

    expect($result->acknowledged_at->toDateTimeString())
        ->toBe('2026-09-12 08:15:00')
        ->and($result->acknowledged_by)
        ->toBe($originalUser->id);

    $this->assertDatabaseHas('alerts', [
        'id' => $alert->id,
        'acknowledged_by' => $originalUser->id,
        'acknowledged_at' => '2026-09-12 08:15:00',
    ]);

    Carbon::setTestNow();
});

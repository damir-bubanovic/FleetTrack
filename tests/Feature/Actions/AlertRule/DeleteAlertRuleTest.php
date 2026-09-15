<?php

declare(strict_types=1);

use App\Actions\AlertRule\DeleteAlertRule;
use App\Models\AlertRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes an alert rule', function (): void {
    $rule = AlertRule::factory()->create();

    app(DeleteAlertRule::class)->execute($rule);

    $this->assertDatabaseMissing('alert_rules', [
        'id' => $rule->id,
    ]);
});

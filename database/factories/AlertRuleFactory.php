<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AlertRule;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertRule>
 */
final class AlertRuleFactory extends Factory
{
    protected $model = AlertRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'vehicle_id' => null,
            'name' => fake()->words(3, true),
            'type' => 'overspeed',
            'severity' => 'warning',
            'conditions' => [
                'speed_limit_kmh' => 90,
            ],
            'is_active' => true,
        ];
    }
}

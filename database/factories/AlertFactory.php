<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
final class AlertFactory extends Factory
{
    protected $model = Alert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'vehicle_id' => null,
            'device_id' => null,
            'geofence_id' => null,
            'type' => 'geofence_enter',
            'severity' => 'info',
            'title' => fake()->sentence(4),
            'message' => fake()->sentence(),
            'traccar_event_id' => fake()->unique()->numberBetween(1, 2_000_000_000),
            'occurred_at' => now(),
            'acknowledged_at' => null,
            'acknowledged_by' => null,
        ];
    }
}

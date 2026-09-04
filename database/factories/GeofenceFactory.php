<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Geofence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Geofence>
 */
class GeofenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'traccar_geofence_id' => null,
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'area' => 'CIRCLE ('.fake()->latitude().' '.fake()->longitude().', 100)',
            'is_active' => true,
            'last_sync_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function synced(): static
    {
        return $this->state(fn (array $attributes): array => [
            'traccar_geofence_id' => fake()->unique()->numberBetween(1, 1000000),
            'last_sync_at' => now(),
        ]);
    }
}

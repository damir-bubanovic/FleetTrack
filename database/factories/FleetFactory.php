<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Fleet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fleet>
 */
class FleetFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),

            'name' => fake()->company().' Fleet',

            'code' => strtoupper(fake()->bothify('FLT-###')),

            'email' => fake()->companyEmail(),

            'phone' => fake()->phoneNumber(),

            'address' => fake()->address(),

            'latitude' => fake()->latitude(),

            'longitude' => fake()->longitude(),

            'timezone' => config('app.timezone'),

            'description' => fake()->optional()->paragraph(),

            'is_active' => true,
        ];
    }

    /**
     * Inactive fleet.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}

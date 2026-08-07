<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Fleet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * The name of the corresponding model.
     *
     * @var class-string<Driver>
     */
    protected $model = Driver::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),

            'fleet_id' => Fleet::factory(),

            'user_id' => null,

            'employee_number' => fake()->unique()->bothify('EMP-####'),

            'first_name' => fake()->firstName(),

            'last_name' => fake()->lastName(),

            'phone' => fake()->phoneNumber(),

            'email' => fake()->unique()->safeEmail(),

            'license_number' => fake()->unique()->bothify('LIC-########'),

            'license_category' => fake()->randomElement([
                'B',
                'C',
                'C1',
                'CE',
                'D',
            ]),

            'license_expiry_date' => fake()->dateTimeBetween('+1 year', '+10 years'),

            'employment_date' => fake()->dateTimeBetween('-10 years', 'now'),

            'notes' => fake()->optional()->sentence(),

            'is_active' => true,
        ];
    }

    /**
     * Create an inactive driver.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}

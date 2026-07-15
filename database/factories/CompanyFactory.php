<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $companyName = fake()->company();

        return [
            'name' => $companyName,
            'slug' => Str::slug($companyName).'-'.fake()->unique()->numberBetween(100, 999),

            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),

            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),

            'logo' => null,

            'is_active' => true,

            'settings' => [
                'timezone' => 'Europe/Zagreb',
                'language' => 'en',
                'distance_unit' => 'km',
                'speed_unit' => 'km/h',
            ],
        ];
    }
}

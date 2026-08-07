<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Fleet;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $manufacturers = [
            'Mercedes-Benz',
            'Volvo',
            'Scania',
            'MAN',
            'DAF',
            'Iveco',
            'Renault',
        ];

        $models = [
            'Actros',
            'FH16',
            'R500',
            'TGX',
            'XF',
            'S-Way',
            'T High',
        ];

        $colors = [
            'White',
            'Black',
            'Silver',
            'Blue',
            'Red',
            'Gray',
        ];

        $fuelTypes = [
            'Diesel',
            'Electric',
            'Hybrid',
        ];

        return [
            'company_id' => Company::factory(),

            'fleet_id' => Fleet::factory(),

            'registration_number' => strtoupper(
                fake()->bothify('??###??')
            ),

            'vin' => strtoupper(
                fake()->bothify('#################')
            ),

            'manufacturer' => fake()->randomElement($manufacturers),

            'model' => fake()->randomElement($models),

            'year' => fake()->numberBetween(
                2015,
                now()->year
            ),

            'color' => fake()->randomElement($colors),

            'fuel_type' => fake()->randomElement($fuelTypes),

            'transmission' => fake()->randomElement([
                'Manual',
                'Automatic',
            ]),

            'odometer' => fake()->numberBetween(
                0,
                900000
            ),

            'notes' => fake()->optional()->paragraph(),

            'is_active' => true,
        ];
    }

    /**
     * Inactive vehicle.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
<?php

namespace Database\Factories;

use App\Enums\DeviceStatus;
use App\Models\Company;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'vehicle_id' => null,

            'traccar_device_id' => fake()->unique()->numberBetween(100000, 999999),

            'name' => fake()->bothify('Tracker-###'),

            'unique_id' => fake()->unique()->numerify('###########'),

            'model' => fake()->randomElement([
                'Teltonika FMB920',
                'Teltonika FMC130',
                'Queclink GV300',
                'Ruptela ECO4',
            ]),

            'phone' => fake()->optional()->phoneNumber(),

            'status' => fake()->randomElement(DeviceStatus::cases()),

            'last_sync_at' => now()->subMinutes(rand(1, 120)),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => DeviceStatus::INACTIVE,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn () => [
            'status' => DeviceStatus::MAINTENANCE,
        ]);
    }
}

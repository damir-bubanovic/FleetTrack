<?php

namespace Database\Seeders;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Seed the application's devices.
     */
    public function run(): void
    {
        Vehicle::query()
            ->orderBy('id')
            ->each(function (Vehicle $vehicle): void {
                Device::factory()->create([
                    'company_id' => $vehicle->company_id,
                    'vehicle_id' => $vehicle->id,
                    'name' => sprintf(
                        '%s %s GPS',
                        $vehicle->manufacturer,
                        $vehicle->model,
                    ),
                    'unique_id' => sprintf(
                        'FT-%06d',
                        $vehicle->id,
                    ),
                    'status' => DeviceStatus::ACTIVE,
                    'traccar_device_id' => null,
                    'last_sync_at' => null,
                ]);
            });
    }
}

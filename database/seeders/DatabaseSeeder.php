<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            CompanySeeder::class,

            RoleSeeder::class,
            CompanyRoleSeeder::class,

            UserSeeder::class,

            FleetSeeder::class,
            VehicleSeeder::class,
            DriverSeeder::class,
            DeviceSeeder::class,
            GeofenceSeeder::class,
            AlertRuleSeeder::class,
            AlertSeeder::class,
        ]);
    }
}

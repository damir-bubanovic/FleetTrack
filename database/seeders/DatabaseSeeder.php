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
            FleetSeeder::class,
            VehicleSeeder::class,
            RoleSeeder::class,
            CompanyRoleSeeder::class,
            UserSeeder::class,
        ]);
    }
}

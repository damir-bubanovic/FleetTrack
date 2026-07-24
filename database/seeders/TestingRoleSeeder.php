<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestingRoleSeeder extends Seeder
{
    /**
     * Seed only the global data required for tests.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
    }
}
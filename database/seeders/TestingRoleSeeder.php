<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class TestingRoleSeeder extends Seeder
{
    /**
     * Seed only the data required for tests.
     */
    public function run(): void
    {
        // Ensure the system company exists.
        $this->call([
            CompanySeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        // Seed company-specific roles for all companies.
        $this->call([
            CompanyRoleSeeder::class,
        ]);
    }
}
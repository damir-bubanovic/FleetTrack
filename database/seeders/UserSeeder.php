<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's users.
     */
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);

        $permissionRegistrar->forgetCachedPermissions();

        /*
         |--------------------------------------------------------------------------
         | System Company
         |--------------------------------------------------------------------------
         */

        $systemCompany = Company::query()
            ->where('slug', config('fleettrack.system_company_slug'))
            ->firstOrFail();

        /*
         |--------------------------------------------------------------------------
         | Global Administrator
         |--------------------------------------------------------------------------
         */

        setPermissionsTeamId($systemCompany->id);

        $superAdmin = User::factory()->create([
            'company_id' => $systemCompany->id,
            'name'       => 'System Administrator',
            'email'      => 'admin@fleettrack.test',
        ]);

        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        /*
         |--------------------------------------------------------------------------
         | Company Users
         |--------------------------------------------------------------------------
         */

        Company::query()->each(function (Company $company): void {

            setPermissionsTeamId($company->id);

            $companyAdmin = User::factory()->create([
                'company_id' => $company->id,
                'name'       => "{$company->name} Administrator",
                'email'      => "admin.{$company->id}@fleettrack.test",
            ]);

            $companyAdmin->assignRole(UserRole::CompanyAdmin->value);

            $fleetManager = User::factory()->create([
                'company_id' => $company->id,
                'name'       => "{$company->name} Fleet Manager",
                'email'      => "manager.{$company->id}@fleettrack.test",
            ]);

            $fleetManager->assignRole(UserRole::FleetManager->value);

            User::factory()
                ->count(5)
                ->create([
                    'company_id' => $company->id,
                ])
                ->each(function (User $driver): void {
                    $driver->assignRole(UserRole::Driver->value);
                });
        });

        setPermissionsTeamId(null);

        $permissionRegistrar->forgetCachedPermissions();
    }
}
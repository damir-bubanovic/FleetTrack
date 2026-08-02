<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CompanyRoleSeeder extends Seeder
{
    /**
     * Seed company-specific roles.
     */
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);

        $permissionRegistrar->forgetCachedPermissions();

        $provisionCompanyRoles = app(\App\Actions\Company\ProvisionCompanyRoles::class);

        Company::query()->each(function (Company $company) use ($provisionCompanyRoles): void {
            $provisionCompanyRoles->handle($company);
        });

        setPermissionsTeamId(null);

        $permissionRegistrar->forgetCachedPermissions();
    }
    
}
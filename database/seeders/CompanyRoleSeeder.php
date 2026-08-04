<?php

namespace Database\Seeders;

use App\Actions\Company\ProvisionCompanyRoles;
use App\Models\Company;
use Illuminate\Database\Seeder;
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

        $provisionCompanyRoles = app(ProvisionCompanyRoles::class);

        Company::query()->each(function (Company $company) use ($provisionCompanyRoles): void {
            $provisionCompanyRoles->handle($company);
        });

        setPermissionsTeamId(null);

        $permissionRegistrar->forgetCachedPermissions();
    }
}

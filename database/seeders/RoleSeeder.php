<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Seed global and company-scoped roles.
     */
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);

        $permissionRegistrar->forgetCachedPermissions();

        /*
         |--------------------------------------------------------------------------
         | Super Admin Role
         |--------------------------------------------------------------------------
         */

        $primaryCompany = Company::query()
            ->where('slug', 'fleettrack-logistics')
            ->firstOrFail();

        setPermissionsTeamId($primaryCompany->id);

        $superAdmin = Role::findOrCreate(
            UserRole::SuperAdmin->value,
            'web'
        );

        $superAdmin->syncPermissions(
            Permission::all()
        );

        /*
         |--------------------------------------------------------------------------
         | Company Roles
         |--------------------------------------------------------------------------
         */

        Company::query()->each(function (Company $company): void {

            setPermissionsTeamId($company->id);

            $companyAdmin = Role::findOrCreate(
                UserRole::CompanyAdmin->value,
                'web'
            );

            $fleetManager = Role::findOrCreate(
                UserRole::FleetManager->value,
                'web'
            );

            $driver = Role::findOrCreate(
                UserRole::Driver->value,
                'web'
            );

            $companyAdmin->syncPermissions([
                'companies.view',
                'companies.update',

                'users.view',
                'users.create',
                'users.update',
                'users.delete',

                'drivers.view',
                'drivers.create',
                'drivers.update',
                'drivers.delete',

                'vehicles.view',
                'vehicles.create',
                'vehicles.update',
                'vehicles.delete',

                'devices.view',
                'devices.create',
                'devices.update',
                'devices.delete',

                'trips.view',
                'trips.create',
                'trips.update',
                'trips.delete',

                'geofences.view',
                'geofences.create',
                'geofences.update',
                'geofences.delete',

                'alerts.view',
                'alerts.manage',

                'reports.view',
                'reports.export',

                'settings.view',
                'settings.update',

                'activity-logs.view',

                'tracking.view',
            ]);

            $fleetManager->syncPermissions([
                'users.view',

                'drivers.view',
                'drivers.create',
                'drivers.update',
                'drivers.delete',

                'vehicles.view',
                'vehicles.create',
                'vehicles.update',
                'vehicles.delete',

                'devices.view',
                'devices.create',
                'devices.update',
                'devices.delete',

                'trips.view',
                'trips.create',
                'trips.update',

                'geofences.view',
                'geofences.create',
                'geofences.update',
                'geofences.delete',

                'alerts.view',
                'alerts.manage',

                'reports.view',
                'reports.export',

                'tracking.view',
            ]);

            $driver->syncPermissions([
                'trips.view',
                'tracking.view',
                'tracking.start',
                'tracking.stop',

                'vehicle-issues.create',
                'vehicle-issues.view-own',
            ]);
        });

        setPermissionsTeamId(null);

        $permissionRegistrar->forgetCachedPermissions();
    }
}

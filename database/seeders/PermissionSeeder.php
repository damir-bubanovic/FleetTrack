<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the application's permissions.
     */
    public function run(): void
    {
        /** @var PermissionRegistrar $permissionRegistrar */
        $permissionRegistrar = resolve(PermissionRegistrar::class);

        $permissionRegistrar->forgetCachedPermissions();

        $permissions = [
            'companies.view',
            'companies.create',
            'companies.update',
            'companies.delete',

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
            'tracking.start',
            'tracking.stop',

            'vehicle-issues.create',
            'vehicle-issues.view-own',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $permissionRegistrar->forgetCachedPermissions();
    }
}

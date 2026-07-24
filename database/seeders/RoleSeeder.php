<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Seed global roles.
     */
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);

        $permissionRegistrar->forgetCachedPermissions();

        // Global roles (no company/team)
        setPermissionsTeamId(null);

        $superAdmin = Role::findOrCreate(
            UserRole::SuperAdmin->value,
            'web'
        );

        $superAdmin->syncPermissions(
            Permission::all()
        );

        $permissionRegistrar->forgetCachedPermissions();
    }
}
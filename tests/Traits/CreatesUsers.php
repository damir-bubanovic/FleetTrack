<?php

namespace Tests\Traits;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

trait CreatesUsers
{
    protected function createSuperAdmin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $systemCompany = Company::firstOrCreate(
            ['slug' => config('fleettrack.system_company_slug')],
            [
                'name'        => 'FleetTrack Logistics',
                'email'       => 'admin@fleettrack.test',
                'phone'       => '+385000000000',
                'address'     => 'FleetTrack HQ',
                'city'        => 'Zagreb',
                'state'       => 'Zagreb',
                'postal_code' => '10000',
                'country'     => 'Croatia',
                'is_active'   => true,
                'settings'    => [
                    'timezone' => 'Europe/Zagreb',
                    'language' => 'en',
                ],
            ]
        );

        setPermissionsTeamId($systemCompany->id);

        $user = User::factory()->create([
            'company_id' => $systemCompany->id,
        ]);

        $user->assignRole(UserRole::SuperAdmin->value);

        return $user;
    }

    protected function createCompanyAdmin(Company $company): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        setPermissionsTeamId($company->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $user->assignRole(UserRole::CompanyAdmin->value);

        return $user;
    }

    protected function actingAsSuperAdmin(): User
    {
        /** @var \Tests\TestCase $this */
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        return $user;
    }

    protected function actingAsCompanyAdmin(Company $company): User
    {
        /** @var \Tests\TestCase $this */
        $user = $this->createCompanyAdmin($company);

        $this->actingAs($user);

        return $user;
    }
}
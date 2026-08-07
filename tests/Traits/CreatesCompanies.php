<?php

namespace Tests\Traits;

use App\Actions\Company\ProvisionCompanyRoles;
use App\Models\Company;
use App\Models\Fleet;
use Illuminate\Database\Eloquent\Collection;

trait CreatesCompanies
{
    protected function createCompany(array $attributes = []): Company
    {
        $company = Company::factory()->create($attributes);

        app(ProvisionCompanyRoles::class)
            ->handle($company);

        return $company;
    }

    protected function createCompanies(int $count = 3): Collection
    {
        $companies = Company::factory()
            ->count($count)
            ->create();

        $companies->each(function (Company $company): void {
            app(ProvisionCompanyRoles::class)
                ->handle($company);
        });

        return $companies;
    }

    /**
     * Create a company with a fleet for feature tests.
     *
     * @return array{
     *     company: Company,
     *     fleet: Fleet
     * }
     */
    protected function createCompanyWithFleet(
        array $companyAttributes = [],
        array $fleetAttributes = [],
    ): array {
        $company = $this->createCompany($companyAttributes);

        $fleet = Fleet::factory()->create(
            array_merge(
                [
                    'company_id' => $company->id,
                ],
                $fleetAttributes
            )
        );

        return [
            'company' => $company,
            'fleet' => $fleet,
        ];
    }
}

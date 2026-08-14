<?php

namespace App\Actions\Company;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateCompany
{
    public function __construct(
        private readonly ProvisionCompanyRoles $provisionCompanyRoles,
    ) {
    }

    /**
     * Create a new company.
     *
     * @param array<string, mixed> $data
     */
    public function handle(array $data): Company
    {
        return DB::transaction(function () use ($data): Company {

            if (
                isset($data['logo']) &&
                $data['logo'] instanceof UploadedFile
            ) {
                $data['logo'] = $data['logo']->store(
                    'companies/logos',
                    'public',
                );
            }

            $data['is_active'] ??= true;

            $data['settings'] ??= [
                'timezone' => config('app.timezone'),
                'language' => config('app.locale'),
                'distance_unit' => 'km',
                'speed_unit' => 'km/h',
            ];

            $company = Company::create($data);

            $this->provisionCompanyRoles->handle($company);

            return $company;
        });
    }
}
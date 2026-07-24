<?php

namespace App\Actions\Company;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateCompany
{
    /**
     * Update an existing company.
     */
    public function handle(Company $company, array $data): Company
    {
        return DB::transaction(function () use ($company, $data): Company {

            if (
                isset($data['logo']) &&
                $data['logo'] instanceof UploadedFile
            ) {
                if (
                    $company->logo !== null &&
                    Storage::disk('public')->exists($company->logo)
                ) {
                    Storage::disk('public')->delete($company->logo);
                }

                $data['logo'] = $data['logo']->store(
                    'companies/logos',
                    'public'
                );
            }

            $company->update($data);

            return $company->fresh();
        });
    }
}

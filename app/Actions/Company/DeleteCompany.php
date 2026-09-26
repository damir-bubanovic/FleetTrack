<?php

namespace App\Actions\Company;

use App\Models\Company;
use Illuminate\Validation\ValidationException;

class DeleteCompany
{
    public function handle(Company $company): void
    {
        if ($company->fleets()->exists()) {
            throw ValidationException::withMessages([
                'company' => [
                    'The company cannot be deleted while it has active fleets.',
                ],
            ]);
        }

        $company->delete();
    }
}
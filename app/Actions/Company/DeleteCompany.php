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

        if ($company->devices()->exists()) {
            throw ValidationException::withMessages([
                'company' => [
                    'The company cannot be deleted while it has active devices.',
                ],
            ]);
        }

        if ($company->geofences()->exists()) {
            throw ValidationException::withMessages([
                'company' => [
                    'The company cannot be deleted while it has active geofences.',
                ],
            ]);
        }

        if ($company->alertRules()->exists()) {
            throw ValidationException::withMessages([
                'company' => [
                    'The company cannot be deleted while it has active alert rules.',
                ],
            ]);
        }

        if ($company->users()->exists()) {
            throw ValidationException::withMessages([
                'company' => [
                    'The company cannot be deleted while it has active users.',
                ],
            ]);
        }

        $company->delete();
    }
}

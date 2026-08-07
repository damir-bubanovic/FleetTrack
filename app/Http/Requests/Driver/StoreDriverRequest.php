<?php

namespace App\Http\Requests\Driver;

use App\Models\Driver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to create a driver.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Driver::class) ?? false;
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [

            'fleet_id' => [
                'required',
                'integer',
                Rule::exists('fleets', 'id'),
            ],

            'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('drivers')
                    ->where('company_id', $companyId),
            ],

            'first_name' => [
                'required',
                'string',
                'max:255',
            ],

            'last_name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('drivers')
                    ->where('company_id', $companyId),
            ],

            'license_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('drivers')
                    ->where('company_id', $companyId),
            ],

            'license_category' => [
                'required',
                'string',
                'max:20',
            ],

            'license_expiry_date' => [
                'required',
                'date',
                'after:today',
            ],

            'employment_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Prepare the data before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('employee_number')) {
            $this->merge([
                'employee_number' => strtoupper(
                    trim($this->input('employee_number'))
                ),
            ]);
        }

        if ($this->has('license_number')) {
            $this->merge([
                'license_number' => strtoupper(
                    trim($this->input('license_number'))
                ),
            ]);
        }
    }
}

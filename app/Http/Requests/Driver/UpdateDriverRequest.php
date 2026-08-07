<?php

namespace App\Http\Requests\Driver;

use App\Models\Driver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to update the driver.
     */
    public function authorize(): bool
    {
        /** @var Driver|null $driver */
        $driver = $this->route('driver');

        return $driver !== null
            && ($this->user()?->can('update', $driver) ?? false);
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Driver $driver */
        $driver = $this->route('driver');

        return [
            'fleet_id' => [
                'required',
                'integer',
                Rule::exists('fleets', 'id')
                    ->where('company_id', $driver->company_id),
            ],

            'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('drivers', 'employee_number')
                    ->where('company_id', $driver->company_id)
                    ->ignore($driver),
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
                Rule::unique('drivers', 'email')
                    ->where('company_id', $driver->company_id)
                    ->ignore($driver),
            ],

            'license_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('drivers', 'license_number')
                    ->where('company_id', $driver->company_id)
                    ->ignore($driver),
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
        if ($this->has('employee_number') && is_string($this->input('employee_number'))) {
            $this->merge([
                'employee_number' => strtoupper(
                    trim($this->input('employee_number'))
                ),
            ]);
        }

        if ($this->has('license_number') && is_string($this->input('license_number'))) {
            $this->merge([
                'license_number' => strtoupper(
                    trim($this->input('license_number'))
                ),
            ]);
        }
    }
}

<?php

namespace App\Http\Requests\Fleet;

use App\Enums\UserRole;
use App\Models\Fleet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFleetRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to create a fleet.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Fleet::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        $isSuperAdmin = $user?->hasRole(
            UserRole::SuperAdmin->value,
        ) ?? false;

        $companyId = $isSuperAdmin
            ? $this->integer('company_id')
            : $user?->company_id;

        return [
            'company_id' => [
                Rule::requiredIf($isSuperAdmin),
                'integer',
                'exists:companies,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fleets', 'name')
                    ->where('company_id', $companyId),
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('fleets', 'code')
                    ->where('company_id', $companyId),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'timezone' => [
                'required',
                'string',
                'timezone',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code') && is_string($this->input('code'))) {
            $this->merge([
                'code' => strtoupper(trim($this->input('code'))),
            ]);
        }

        if (
            $this->input('timezone') === null
            || $this->input('timezone') === ''
        ) {
            $this->merge([
                'timezone' => config('app.timezone'),
            ]);
        }
    }
}

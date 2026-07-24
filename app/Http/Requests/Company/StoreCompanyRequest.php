<?php

namespace App\Http\Requests\Company;

use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to create a company.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Company::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique(Company::class, 'slug'),
            ],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],

            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:255'],

            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'is_active' => ['sometimes', 'boolean'],

            'settings' => ['nullable', 'array'],
            'settings.timezone' => ['sometimes', 'string', 'timezone'],
            'settings.language' => ['sometimes', 'string', 'max:10'],
            'settings.distance_unit' => [
                'sometimes',
                'string',
                Rule::in(['km', 'mi']),
            ],
            'settings.speed_unit' => [
                'sometimes',
                'string',
                Rule::in(['km/h', 'mph']),
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slug') && is_string($this->input('slug'))) {
            $this->merge([
                'slug' => strtolower(trim($this->input('slug'))),
            ]);
        }
    }
}

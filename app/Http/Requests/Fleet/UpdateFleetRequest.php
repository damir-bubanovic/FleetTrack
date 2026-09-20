<?php

namespace App\Http\Requests\Fleet;

use App\Models\Fleet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFleetRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to update the fleet.
     */
    public function authorize(): bool
    {
        /** @var Fleet|null $fleet */
        $fleet = $this->route('fleet');

        return $fleet !== null
            && ($this->user()?->can('update', $fleet) ?? false);
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Fleet $fleet */
        $fleet = $this->route('fleet');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Fleet::class, 'name')
                    ->where('company_id', $fleet->company_id)
                    ->ignore($fleet),
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique(Fleet::class, 'code')
                    ->where('company_id', $fleet->company_id)
                    ->ignore($fleet),
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
                'nullable',
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

    /**
     * Prepare the data before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code') && is_string($this->input('code'))) {
            $this->merge([
                'code' => strtoupper(trim($this->input('code'))),
            ]);
        }

        if (
            $this->has('timezone')
            && ($this->input('timezone') === null || $this->input('timezone') === '')
        ) {
            $this->merge([
                'timezone' => config('app.timezone'),
            ]);
        }
    }
}

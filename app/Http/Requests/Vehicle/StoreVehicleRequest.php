<?php

namespace App\Http\Requests\Vehicle;

use App\Models\Vehicle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to create a vehicle.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vehicle::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fleet_id' => [
                'required',
                'integer',
                'exists:fleets,id',
            ],

            'registration_number' => [
                'required',
                'string',
                'max:50',
            ],

            'vin' => [
                'required',
                'string',
                'size:17',
                Rule::unique('vehicles', 'vin'),
            ],

            'manufacturer' => [
                'required',
                'string',
                'max:100',
            ],

            'model' => [
                'required',
                'string',
                'max:100',
            ],

            'year' => [
                'required',
                'integer',
                'min:1900',
                'max:'.(date('Y') + 1),
            ],

            'color' => [
                'nullable',
                'string',
                'max:50',
            ],

            'fuel_type' => [
                'required',
                'string',
                'max:50',
            ],

            'transmission' => [
                'required',
                'string',
                'max:50',
            ],

            'odometer' => [
                'nullable',
                'integer',
                'min:0',
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

    protected function prepareForValidation(): void
    {
        if ($this->input('odometer') === null || $this->input('odometer') === '') {
            $this->merge([
                'odometer' => 0,
            ]);
        }
    }
}

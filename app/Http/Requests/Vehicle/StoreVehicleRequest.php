<?php

namespace App\Http\Requests\Vehicle;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>|string>
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
                'max:255',
            ],

            'vin' => [
                'required',
                'string',
                'size:17',
                'unique:vehicles,vin',
            ],

            'manufacturer' => [
                'required',
                'string',
                'max:255',
            ],

            'model' => [
                'required',
                'string',
                'max:255',
            ],

            'year' => [
                'required',
                'integer',
                'min:1900',
                'max:'.(now()->year + 1),
            ],

            'color' => [
                'nullable',
                'string',
                'max:255',
            ],

            'fuel_type' => [
                'required',
                'string',
                'max:255',
            ],

            'transmission' => [
                'required',
                'string',
                'max:255',
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
}

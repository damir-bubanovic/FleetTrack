<?php

namespace App\Http\Requests\Tracking;

use Illuminate\Foundation\Http\FormRequest;

class LivePositionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fleet_id' => [
                'nullable',
                'integer',
                'exists:fleets,id',
            ],
            'vehicle_id' => [
                'nullable',
                'integer',
                'exists:vehicles,id',
            ],
        ];
    }
}

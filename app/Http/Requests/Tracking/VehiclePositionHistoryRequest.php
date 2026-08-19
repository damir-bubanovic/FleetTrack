<?php

namespace App\Http\Requests\Tracking;

use Illuminate\Foundation\Http\FormRequest;

class VehiclePositionHistoryRequest extends FormRequest
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
            'from' => [
                'required',
                'date',
            ],
            'to' => [
                'required',
                'date',
                'after:from',
            ],
        ];
    }
}

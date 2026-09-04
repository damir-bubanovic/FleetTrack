<?php

declare(strict_types=1);

namespace App\Http\Requests\Geofence;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeofenceRequest extends FormRequest
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
        $isSuperAdmin = $this->user()->hasRole(UserRole::SuperAdmin->value);

        return [
            'company_id' => [
                Rule::requiredIf($isSuperAdmin),
                'nullable',
                'integer',
                'exists:companies,id',
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'area' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

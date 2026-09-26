<?php

declare(strict_types=1);

namespace App\Http\Requests\AlertRule;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAlertRuleRequest extends FormRequest
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
            'company_id' => [
                Rule::requiredIf(
                    fn (): bool => $this->user()?->hasRole(
                        UserRole::SuperAdmin->value,
                    ) ?? false,
                ),
                'integer',
                'exists:companies,id',
            ],
            'vehicle_id' => [
                'nullable',
                'integer',
                'exists:vehicles,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'type' => [
                'required',
                'string',
                Rule::in([
                    'overspeed',
                    'geofence_enter',
                    'geofence_exit',
                    'ignition_on',
                    'ignition_off',
                    'device_offline',
                ]),
            ],
            'severity' => [
                'required',
                'string',
                Rule::in([
                    'info',
                    'warning',
                    'critical',
                ]),
            ],
            'conditions' => [
                'present',
                'array',
            ],
            'conditions.speed_limit_kmh' => [
                Rule::requiredIf(
                    fn (): bool => $this->input('type') === 'overspeed',
                ),
                'numeric',
                'gt:0',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}

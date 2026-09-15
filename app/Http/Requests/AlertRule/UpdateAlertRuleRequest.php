<?php

declare(strict_types=1);

namespace App\Http\Requests\AlertRule;

use App\Models\AlertRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAlertRuleRequest extends FormRequest
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
                'sometimes',
                'integer',
                'exists:companies,id',
            ],
            'vehicle_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:vehicles,id',
            ],
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'type' => [
                'sometimes',
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
                'sometimes',
                'string',
                Rule::in([
                    'info',
                    'warning',
                    'critical',
                ]),
            ],
            'conditions' => [
                'sometimes',
                'array',
            ],
            'conditions.speed_limit_kmh' => [
                Rule::requiredIf(
                    fn (): bool => $this->requiresOverspeedSpeedLimit(),
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

    private function requiresOverspeedSpeedLimit(): bool
    {
        if ($this->input('type') === 'overspeed') {
            return true;
        }

        if (! $this->has('conditions') || $this->has('type')) {
            return false;
        }

        $alertRule = $this->route('alertRule');

        return $alertRule instanceof AlertRule
            && $alertRule->type === 'overspeed';
    }
}

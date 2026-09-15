<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AlertRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AlertRule
 */
final class AlertRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'vehicle_id' => $this->vehicle_id,
            'name' => $this->name,
            'type' => $this->type,
            'severity' => $this->severity,
            'conditions' => $this->conditions,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

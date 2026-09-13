<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Alert
 */
final class AlertResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'vehicle_id' => $this->vehicle_id,
            'device_id' => $this->device_id,
            'geofence_id' => $this->geofence_id,

            'type' => $this->type,
            'severity' => $this->severity,

            'title' => $this->title,
            'message' => $this->message,

            'occurred_at' => $this->occurred_at,
            'acknowledged_at' => $this->acknowledged_at,
            'acknowledged_by' => $this->acknowledged_by,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

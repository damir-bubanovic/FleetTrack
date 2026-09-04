<?php

declare(strict_types=1);

namespace App\Http\Resources\Geofence;

use App\Models\Geofence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Geofence
 */
class GeofenceResource extends JsonResource
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
            'traccar_geofence_id' => $this->traccar_geofence_id,
            'name' => $this->name,
            'description' => $this->description,
            'area' => $this->area,
            'is_active' => $this->is_active,
            'last_sync_at' => $this->last_sync_at,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

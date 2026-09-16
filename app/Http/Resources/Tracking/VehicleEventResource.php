<?php

declare(strict_types=1);

namespace App\Http\Resources\Tracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'device_id' => $this->resource['deviceId'] ?? null,
            'type' => $this->resource['type'] ?? null,
            'event_time' => $this->resource['eventTime'] ?? null,
            'position_id' => $this->resource['positionId'] ?? null,
            'geofence_id' => $this->resource['geofenceId'] ?? null,
            'maintenance_id' => $this->resource['maintenanceId'] ?? null,
            'attributes' => $this->resource['attributes'] ?? [],
        ];
    }
}

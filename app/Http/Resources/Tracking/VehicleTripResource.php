<?php

namespace App\Http\Resources\Tracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleTripResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $distance = $this->resource['distance'] ?? null;

        return [
            'device_id' => $this->resource['deviceId'] ?? null,
            'driver_id' => $this->resource['driverUniqueId'] ?? null,
            'started_at' => $this->resource['startTime'] ?? null,
            'ended_at' => $this->resource['endTime'] ?? null,
            'start_latitude' => $this->resource['startLat'] ?? null,
            'start_longitude' => $this->resource['startLon'] ?? null,
            'end_latitude' => $this->resource['endLat'] ?? null,
            'end_longitude' => $this->resource['endLon'] ?? null,
            'distance_km' => is_numeric($distance)
                ? round((float) $distance / 1000, 2)
                : null,
            'duration_seconds' => $this->resource['duration'] ?? null,
            'average_speed' => $this->resource['averageSpeed'] ?? null,
            'max_speed' => $this->resource['maxSpeed'] ?? null,
            'speed_unit' => 'knots',
            'start_address' => $this->resource['startAddress'] ?? null,
            'end_address' => $this->resource['endAddress'] ?? null,
        ];
    }
}

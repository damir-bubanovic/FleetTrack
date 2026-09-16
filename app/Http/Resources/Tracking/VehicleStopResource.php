<?php

declare(strict_types=1);

namespace App\Http\Resources\Tracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleStopResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'device_id' => $this->resource['deviceId'] ?? null,
            'address' => $this->resource['address'] ?? null,
            'latitude' => $this->resource['latitude'] ?? null,
            'longitude' => $this->resource['longitude'] ?? null,
            'start_time' => $this->resource['startTime'] ?? null,
            'end_time' => $this->resource['endTime'] ?? null,
            'duration' => $this->resource['duration'] ?? null,
            'engine_hours' => $this->resource['engineHours'] ?? null,
        ];
    }
}

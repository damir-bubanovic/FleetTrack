<?php

namespace App\Http\Resources\Tracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleTripSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'position_count' => $this->resource['position_count'],
            'started_at' => $this->resource['started_at'],
            'ended_at' => $this->resource['ended_at'],
            'duration_seconds' => $this->resource['duration_seconds'],
            'distance_km' => $this->resource['distance_km'],
        ];
    }
}

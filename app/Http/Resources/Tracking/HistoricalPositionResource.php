<?php

namespace App\Http\Resources\Tracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoricalPositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'device_id' => $this->resource['deviceId'] ?? null,
            'latitude' => $this->resource['latitude'] ?? null,
            'longitude' => $this->resource['longitude'] ?? null,
            'altitude' => $this->resource['altitude'] ?? null,
            'speed' => $this->resource['speed'] ?? null,
            'course' => $this->resource['course'] ?? null,
            'accuracy' => $this->resource['accuracy'] ?? null,
            'fix_time' => $this->resource['fixTime'] ?? null,
            'device_time' => $this->resource['deviceTime'] ?? null,
            'server_time' => $this->resource['serverTime'] ?? null,
            'attributes' => $this->resource['attributes'] ?? [],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources\Tracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleRouteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'device_id' => $this->resource['deviceId'] ?? null,
            'protocol' => $this->resource['protocol'] ?? null,
            'device_time' => $this->resource['deviceTime'] ?? null,
            'fix_time' => $this->resource['fixTime'] ?? null,
            'server_time' => $this->resource['serverTime'] ?? null,
            'latitude' => $this->resource['latitude'] ?? null,
            'longitude' => $this->resource['longitude'] ?? null,
            'altitude' => $this->resource['altitude'] ?? null,
            'speed' => $this->resource['speed'] ?? null,
            'course' => $this->resource['course'] ?? null,
            'accuracy' => $this->resource['accuracy'] ?? null,
            'address' => $this->resource['address'] ?? null,
            'attributes' => $this->resource['attributes'] ?? [],
        ];
    }
}

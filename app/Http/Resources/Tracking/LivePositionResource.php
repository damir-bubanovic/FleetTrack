<?php

namespace App\Http\Resources\Tracking;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LivePositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $device = $this->resource['device'];
        $position = $this->resource['position'];
        $fixTime = isset($position['fixTime'])
            ? CarbonImmutable::parse($position['fixTime'])
            : null;

        $isOnline = $fixTime !== null
            && $fixTime->greaterThanOrEqualTo(now()->subMinutes(5));

        return [
            'device' => [
                'id' => $device?->id,
                'name' => $device?->name,
                'unique_id' => $device?->unique_id,
                'traccar_device_id' => $device?->traccar_device_id,
            ],
            'vehicle' => $device?->vehicle ? [
                'id' => $device->vehicle->id,
                'name' => $device->vehicle->name,
            ] : null,
            'status' => [
                'online' => $isOnline,
                'last_seen_at' => $fixTime?->toISOString(),
            ],
            'position' => [
                'id' => $position['id'] ?? null,
                'device_id' => $position['deviceId'] ?? null,
                'latitude' => $position['latitude'] ?? null,
                'longitude' => $position['longitude'] ?? null,
                'altitude' => $position['altitude'] ?? null,
                'speed' => $position['speed'] ?? null,
                'course' => $position['course'] ?? null,
                'accuracy' => $position['accuracy'] ?? null,
                'fix_time' => $position['fixTime'] ?? null,
                'server_time' => $position['serverTime'] ?? null,
                'attributes' => $position['attributes'] ?? [],
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources\Tracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleHoursResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'device_id' => $this->resource['deviceId'] ?? null,
            'device_name' => $this->resource['deviceName'] ?? null,
            'hours' => $this->resource['hours'] ?? null,
        ];
    }
}

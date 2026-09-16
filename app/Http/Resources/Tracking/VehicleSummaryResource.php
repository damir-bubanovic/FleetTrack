<?php

declare(strict_types=1);

namespace App\Http\Resources\Tracking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'device_id' => $this->resource['deviceId'] ?? null,
            'device_name' => $this->resource['deviceName'] ?? null,
            'distance' => $this->resource['distance'] ?? null,
            'average_speed' => $this->resource['averageSpeed'] ?? null,
            'max_speed' => $this->resource['maxSpeed'] ?? null,
            'spent_fuel' => $this->resource['spentFuel'] ?? null,
            'start_odometer' => $this->resource['startOdometer'] ?? null,
            'end_odometer' => $this->resource['endOdometer'] ?? null,
        ];
    }
}

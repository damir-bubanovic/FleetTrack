<?php

namespace App\Http\Resources\Vehicle;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vehicle
 */
class VehicleResource extends JsonResource
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

            'fleet_id' => $this->fleet_id,

            'registration_number' => $this->registration_number,

            'vin' => $this->vin,

            'manufacturer' => $this->manufacturer,

            'model' => $this->model,

            'year' => $this->year,

            'color' => $this->color,

            'fuel_type' => $this->fuel_type,

            'transmission' => $this->transmission,

            'odometer' => $this->odometer,

            'notes' => $this->notes,

            'is_active' => $this->is_active,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Http\Resources\Fleet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Fleet
 */
class FleetResource extends JsonResource
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

            'name' => $this->name,
            'code' => $this->code,

            'email' => $this->email,
            'phone' => $this->phone,

            'address' => $this->address,

            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            'timezone' => $this->timezone,

            'description' => $this->description,

            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
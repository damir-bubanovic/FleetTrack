<?php

namespace App\Http\Resources\Device;

use App\Http\Resources\Company\CompanyResource;
use App\Http\Resources\Vehicle\VehicleResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Device
 */
class DeviceResource extends JsonResource
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
            'vehicle_id' => $this->vehicle_id,
            'traccar_device_id' => $this->traccar_device_id,
            'name' => $this->name,
            'unique_id' => $this->unique_id,
            'model' => $this->model,
            'phone' => $this->phone,
            'status' => $this->status,
            'last_sync_at' => $this->last_sync_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'company' => CompanyResource::make($this->whenLoaded('company')),
            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle')),
        ];
    }
}

<?php

namespace App\Http\Resources\Driver;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Driver
 */
class DriverResource extends JsonResource
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
            'user_id' => $this->user_id,

            'employee_number' => $this->employee_number,

            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),

            'phone' => $this->phone,
            'email' => $this->email,

            'license_number' => $this->license_number,
            'license_category' => $this->license_category,
            'license_expiry_date' => $this->license_expiry_date,

            'employment_date' => $this->employment_date,

            'notes' => $this->notes,

            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

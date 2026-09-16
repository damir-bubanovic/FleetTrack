<?php

declare(strict_types=1);

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DashboardOverviewResource extends JsonResource
{
    /**
     * @return array<string, int>
     */
    public function toArray(Request $request): array
    {
        return [
            'companies' => (int) ($this->resource['companies'] ?? 0),
            'fleets' => (int) ($this->resource['fleets'] ?? 0),
            'vehicles' => (int) ($this->resource['vehicles'] ?? 0),
            'devices' => (int) ($this->resource['devices'] ?? 0),
            'online_vehicles' => (int) ($this->resource['online_vehicles'] ?? 0),
            'offline_vehicles' => (int) ($this->resource['offline_vehicles'] ?? 0),
            'offline_devices' => (int) ($this->resource['offline_devices'] ?? 0),
            'alerts' => (int) ($this->resource['alerts'] ?? 0),
            'unacknowledged_alerts' => (int) ($this->resource['unacknowledged_alerts'] ?? 0),
        ];
    }
}

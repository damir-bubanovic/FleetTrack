<?php

declare(strict_types=1);

namespace App\Services\Traccar;

use App\Data\Traccar\GeofenceData;
use stdClass;

class TraccarGeofenceService
{
    public function __construct(
        private readonly TraccarClient $client,
    ) {}

    public function find(int $id): GeofenceData
    {
        $geofence = $this->client
            ->get("/geofences/{$id}")
            ->throw()
            ->json();

        return GeofenceData::fromArray($geofence);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GeofenceData
    {
        $geofence = $this->client
            ->post('/geofences', $data)
            ->throw()
            ->json();

        return GeofenceData::fromArray($geofence);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        int $id,
        array $data,
    ): GeofenceData {
        $payload = $this->client
            ->get("/geofences/{$id}")
            ->throw()
            ->json();

        $payload = $this->normalizePayload([
            ...$payload,
            ...$data,
        ]);

        $geofence = $this->client
            ->put("/geofences/{$id}", $payload)
            ->throw()
            ->json();

        return GeofenceData::fromArray($geofence);
    }

    public function delete(int $id): void
    {
        $this->client
            ->delete("/geofences/{$id}")
            ->throw();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        if (
            isset($payload['attributes'])
            && is_array($payload['attributes'])
            && $payload['attributes'] === []
        ) {
            $payload['attributes'] = new stdClass;
        }

        return $payload;
    }
}

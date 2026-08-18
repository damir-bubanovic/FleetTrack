<?php

namespace App\Services\Traccar;

use App\Data\Traccar\DeviceData;
use stdClass;

class TraccarDeviceService
{
    public function __construct(
        private readonly TraccarClient $client,
    ) {}

    /**
     * @return array<DeviceData>
     */
    public function all(): array
    {
        $devices = $this->client
            ->get('/devices')
            ->throw()
            ->json();

        return array_map(
            static fn (array $device): DeviceData => DeviceData::fromArray($device),
            $devices,
        );
    }

    public function find(int $id): DeviceData
    {
        $device = $this->client
            ->get("/devices/{$id}")
            ->throw()
            ->json();

        return DeviceData::fromArray($device);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DeviceData
    {
        $device = $this->client
            ->post('/devices', $data)
            ->throw()
            ->json();

        return DeviceData::fromArray($device);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        int $id,
        array $data,
    ): DeviceData {
        $payload = $this->client
            ->get("/devices/{$id}")
            ->throw()
            ->json();

        $payload = $this->normalizePayload([
            ...$payload,
            ...$data,
        ]);

        $device = $this->client
            ->put("/devices/{$id}", $payload)
            ->throw()
            ->json();

        return DeviceData::fromArray($device);
    }

    public function delete(int $id): void
    {
        $this->client
            ->delete("/devices/{$id}")
            ->throw();
    }

    /**
     * Normalize a Traccar device payload before sending it back to the API.
     *
     * Traccar expects the "attributes" field to be a JSON object (`{}`)
     * instead of an empty array (`[]`).
     *
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

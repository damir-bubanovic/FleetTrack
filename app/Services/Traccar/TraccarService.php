<?php

namespace App\Services\Traccar;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TraccarService
{
    private function client(): PendingRequest
    {
        return Http::baseUrl(
            rtrim((string) config('traccar.url'), '/').'/api'
        )
            ->withBasicAuth(
                (string) config('traccar.username'),
                (string) config('traccar.password'),
            )
            ->acceptJson()
            ->timeout((int) config('traccar.timeout', 30))
            ->withOptions([
                'verify' => (bool) config('traccar.verify_ssl', true),
            ]);
    }

    public function devices(): Response
    {
        return $this->client()->get('/devices');
    }

    public function device(int $id): Response
    {
        return $this->client()->get("/devices/{$id}");
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createDevice(array $data): Response
    {
        return $this->client()->post('/devices', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateDevice(int $id, array $data): Response
    {
        return $this->client()->put("/devices/{$id}", $data);
    }

    public function deleteDevice(int $id): Response
    {
        return $this->client()->delete("/devices/{$id}");
    }

    public function positions(array $query = []): Response
    {
        return $this->client()->get('/positions', $query);
    }

    public function session(): Response
    {
        return $this->client()->get('/session');
    }
}
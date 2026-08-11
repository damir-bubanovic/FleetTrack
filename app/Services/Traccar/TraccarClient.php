<?php

namespace App\Services\Traccar;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TraccarClient
{
    protected function client(): PendingRequest
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

    /**
     * @param array<string, mixed> $query
     */
    public function get(string $uri, array $query = []): Response
    {
        return $this->client()->get($uri, $query);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function post(string $uri, array $data = []): Response
    {
        return $this->client()->post($uri, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function put(string $uri, array $data = []): Response
    {
        return $this->client()->put($uri, $data);
    }

    public function delete(string $uri): Response
    {
        return $this->client()->delete($uri);
    }
}
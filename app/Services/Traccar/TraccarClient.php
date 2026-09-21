<?php

namespace App\Services\Traccar;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TraccarClient
{
    public function request(): PendingRequest
    {
        $request = Http::baseUrl(
            rtrim((string) config('traccar.url'), '/').'/api',
        )
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('traccar.timeout', 30))
            ->withOptions([
                'verify' => (bool) config('traccar.verify_ssl', true),
            ]);

        $username = config('traccar.username');
        $password = config('traccar.password');

        if (
            is_string($username)
            && $username !== ''
            && is_string($password)
        ) {
            return $request->withBasicAuth($username, $password);
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $uri, array $query = []): Response
    {
        return $this->request()->get($uri, $query);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function post(string $uri, array $data = []): Response
    {
        return $this->request()->post($uri, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function put(string $uri, array $data = []): Response
    {
        return $this->request()->put($uri, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function delete(string $uri, array $data = []): Response
    {
        return $this->request()->delete($uri, $data);
    }
}

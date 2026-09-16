<?php

namespace App\Services\Traccar;

use Illuminate\Http\Client\Response;

class ReportService
{
    public function __construct(
        private readonly TraccarClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function trips(array $query = []): Response
    {
        return $this->client->get('/reports/trips', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function stops(array $query = []): Response
    {
        return $this->client->get('/reports/stops', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function events(array $query = []): Response
    {
        return $this->client->get('/reports/events', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function route(array $query = []): Response
    {
        return $this->client->get('/reports/route', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function summary(array $query = []): Response
    {
        return $this->client->get('/reports/summary', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function hours(array $query = []): Response
    {
        return $this->client->get('/reports/hours', $query);
    }
}

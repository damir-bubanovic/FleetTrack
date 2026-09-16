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
}

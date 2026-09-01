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
}

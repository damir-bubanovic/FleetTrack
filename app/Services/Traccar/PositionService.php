<?php

namespace App\Services\Traccar;

use Illuminate\Http\Client\Response;

class PositionService
{
    public function __construct(
        private readonly TraccarClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function all(array $query = []): Response
    {
        return $this->client->get('/positions', $query);
    }
}

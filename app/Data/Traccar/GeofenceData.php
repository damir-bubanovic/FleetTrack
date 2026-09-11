<?php

declare(strict_types=1);

namespace App\Data\Traccar;

final readonly class GeofenceData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $area,
        public ?string $description,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            area: (string) $data['area'],
            description: isset($data['description'])
                ? (string) $data['description']
                : null,
        );
    }
}

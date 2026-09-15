<?php

declare(strict_types=1);

namespace App\Data\Traccar;

final readonly class OverspeedEventData
{
    public function __construct(
        public int $eventId,
        public int $deviceId,
        public int $positionId,
        public \DateTimeImmutable $eventTime,
        public float $speed,
        public float $speedLimit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $data['attributes'];

        return new self(
            eventId: (int) $data['id'],
            deviceId: (int) $data['deviceId'],
            positionId: (int) $data['positionId'],
            eventTime: new \DateTimeImmutable((string) $data['eventTime']),
            speed: (float) $attributes['speed'],
            speedLimit: (float) $attributes['speedLimit'],
        );
    }
}

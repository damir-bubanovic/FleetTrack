<?php

declare(strict_types=1);

namespace App\Data\Traccar;

final readonly class IgnitionEventData
{
    public function __construct(
        public int $eventId,
        public string $type,
        public int $deviceId,
        public int $positionId,
        public \DateTimeImmutable $eventTime,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            eventId: (int) $data['id'],
            type: (string) $data['type'],
            deviceId: (int) $data['deviceId'],
            positionId: (int) $data['positionId'],
            eventTime: new \DateTimeImmutable(
                (string) $data['eventTime'],
            ),
        );
    }
}

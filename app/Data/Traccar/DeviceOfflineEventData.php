<?php

declare(strict_types=1);

namespace App\Data\Traccar;

final readonly class DeviceOfflineEventData
{
    public function __construct(
        public int $eventId,
        public int $deviceId,
        public \DateTimeImmutable $eventTime,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            eventId: (int) $data['id'],
            deviceId: (int) $data['deviceId'],
            eventTime: new \DateTimeImmutable(
                (string) $data['eventTime'],
            ),
        );
    }
}

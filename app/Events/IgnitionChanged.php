<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Device;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class IgnitionChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Device $device,
        public readonly string $type,
        public readonly int $positionId,
        public readonly int $traccarEventId,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}

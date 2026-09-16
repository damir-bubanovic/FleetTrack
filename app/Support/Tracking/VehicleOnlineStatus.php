<?php

declare(strict_types=1);

namespace App\Support\Tracking;

use Carbon\CarbonImmutable;

final class VehicleOnlineStatus
{
    private const int ONLINE_THRESHOLD_MINUTES = 5;

    public static function isOnline(?string $fixTime): bool
    {
        if ($fixTime === null) {
            return false;
        }

        return CarbonImmutable::parse($fixTime)
            ->greaterThanOrEqualTo(
                now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES),
            );
    }

    public static function lastSeenAt(?string $fixTime): ?string
    {
        if ($fixTime === null) {
            return null;
        }

        return CarbonImmutable::parse($fixTime)->toISOString();
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Tracking;

use Carbon\CarbonImmutable;
use Throwable;

final class VehicleOnlineStatus
{
    private const int ONLINE_THRESHOLD_MINUTES = 5;

    public static function isOnline(?string $fixTime): bool
    {
        $parsedFixTime = self::parseFixTime($fixTime);

        if ($parsedFixTime === null) {
            return false;
        }

        $now = now();

        return $parsedFixTime->betweenIncluded(
            $now->copy()->subMinutes(self::ONLINE_THRESHOLD_MINUTES),
            $now,
        );
    }

    public static function lastSeenAt(?string $fixTime): ?string
    {
        return self::parseFixTime($fixTime)?->toISOString();
    }

    private static function parseFixTime(?string $fixTime): ?CarbonImmutable
    {
        if ($fixTime === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($fixTime);
        } catch (Throwable) {
            return null;
        }
    }
}

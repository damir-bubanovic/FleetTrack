<?php

namespace App\Actions\Tracking;

use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class GetVehicleTripSummary
{
    public function __construct(
        private readonly GetVehiclePositionHistory $getVehiclePositionHistory,
    ) {}

    /**
     * @return array{
     *     position_count: int,
     *     started_at: string|null,
     *     ended_at: string|null,
     *     duration_seconds: int|null,
     *     distance_km: float,
     *     average_speed: float,
     *     max_speed: float
     * }
     */
    public function handle(
        User $user,
        Vehicle $vehicle,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $positions = $this->getVehiclePositionHistory->handle(
            $user,
            $vehicle,
            $from,
            $to,
        );

        if ($positions === []) {
            return [
                'position_count' => 0,
                'started_at' => null,
                'ended_at' => null,
                'duration_seconds' => null,
                'distance_km' => 0.0,
                'average_speed' => 0.0,
                'max_speed' => 0.0,
            ];
        }

        $first = $positions[0];
        $last = $positions[array_key_last($positions)];

        $startedAt = isset($first['fixTime'])
            ? (string) $first['fixTime']
            : null;

        $endedAt = isset($last['fixTime'])
            ? (string) $last['fixTime']
            : null;

        $durationSeconds = null;

        if ($startedAt !== null && $endedAt !== null) {
            $durationSeconds = (int) CarbonImmutable::parse($startedAt)
                ->diffInSeconds(CarbonImmutable::parse($endedAt));
        }

        $speeds = collect($positions)
            ->pluck('speed')
            ->filter(fn (mixed $speed): bool => is_numeric($speed))
            ->map(fn (mixed $speed): float => (float) $speed);

        $averageSpeed = $speeds->isEmpty()
            ? 0.0
            : round((float) $speeds->avg(), 2);

        $maxSpeed = $speeds->isEmpty()
            ? 0.0
            : round((float) $speeds->max(), 2);

        return [
            'position_count' => count($positions),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
            'distance_km' => $this->calculateDistance($positions),
            'average_speed' => $averageSpeed,
            'max_speed' => $maxSpeed,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $positions
     */
    private function calculateDistance(array $positions): float
    {
        $distance = 0.0;

        for ($index = 1; $index < count($positions); $index++) {
            $previous = $positions[$index - 1];
            $current = $positions[$index];

            if (
                ! isset(
                    $previous['latitude'],
                    $previous['longitude'],
                    $current['latitude'],
                    $current['longitude'],
                )
            ) {
                continue;
            }

            $distance += $this->haversine(
                (float) $previous['latitude'],
                (float) $previous['longitude'],
                (float) $current['latitude'],
                (float) $current['longitude'],
            );
        }

        return round($distance, 2);
    }

    private function haversine(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB,
    ): float {
        $earthRadius = 6371.0;

        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeA))
            * cos(deg2rad($latitudeB))
            * sin($longitudeDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(
            sqrt($a),
            sqrt(1 - $a),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\AlertRule;

use App\Models\AlertRule;
use App\Models\Vehicle;

final class ResolveAlertRule
{
    public function execute(
        Vehicle $vehicle,
        string $type,
        ?float $speedKmh = null,
    ): ?AlertRule {
        $rules = AlertRule::query()
            ->where('company_id', $vehicle->company_id)
            ->where('type', $type)
            ->where('is_active', true)
            ->where(function ($query) use ($vehicle): void {
                $query
                    ->where('vehicle_id', $vehicle->id)
                    ->orWhereNull('vehicle_id');
            })
            ->orderByRaw(
                'CASE WHEN vehicle_id = ? THEN 0 ELSE 1 END',
                [$vehicle->id],
            )
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesConditions(
                $rule,
                $speedKmh,
            )) {
                return $rule;
            }
        }

        return null;
    }

    private function matchesConditions(
        AlertRule $rule,
        ?float $speedKmh,
    ): bool {
        if ($rule->type !== 'overspeed') {
            return true;
        }

        if ($speedKmh === null) {
            return false;
        }

        /** @var array<string, mixed> $conditions */
        $conditions = $rule->conditions;

        $speedLimit = $conditions['speed_limit_kmh'] ?? null;

        if (! is_numeric($speedLimit)) {
            return false;
        }

        return $speedKmh > (float) $speedLimit;
    }
}

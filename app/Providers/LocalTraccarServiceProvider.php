<?php

namespace App\Providers;

use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class LocalTraccarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Http::fake(function ($request) {
            if (! str_ends_with($request->url(), '/positions')) {
                return null;
            }

            $devices = Device::query()
                ->whereNotNull('traccar_device_id')
                ->orderBy('id')
                ->get();

            $positions = $devices
                ->values()
                ->map(function (Device $device, int $index): array {
                    $traccarDeviceId = (int) $device->traccar_device_id;

                    $fixTime = match ($index % 4) {
                        0 => now()->subMinutes(1),
                        1 => now()->subMinutes(2),
                        2 => now()->subMinutes(10),
                        default => now()->subMinutes(15),
                    };

                    return [
                        'id' => 1000 + $traccarDeviceId,
                        'deviceId' => $traccarDeviceId,
                        'protocol' => 'osmand',
                        'serverTime' => now()->toIso8601String(),
                        'deviceTime' => $fixTime->toIso8601String(),
                        'fixTime' => $fixTime->toIso8601String(),
                        'outdated' => false,
                        'valid' => true,
                        'latitude' => 45.8150 + ($index * 0.004),
                        'longitude' => 15.9819 + ($index * 0.004),
                        'altitude' => 120 + $index,
                        'speed' => 8 + (($index % 8) * 4),
                        'course' => ($index * 37) % 360,
                        'accuracy' => 5.0,
                        'attributes' => [
                            'ignition' => $index % 3 !== 0,
                            'motion' => $index % 2 === 0,
                            'batteryLevel' => 65 + ($index % 35),
                        ],
                    ];
                })
                ->all();

            return Http::response($positions);
        });
    }
}

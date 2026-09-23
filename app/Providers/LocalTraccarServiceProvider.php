<?php

namespace App\Providers;

use App\Models\Device;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class LocalTraccarServiceProvider extends ServiceProvider
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private static array $geofences = [];

    private static int $nextGeofenceId = 1001;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Http::fake(function (Request $request) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            if (! is_string($path)) {
                return Http::response([], 404);
            }

            if ($path === '/api/positions' && $request->method() === 'GET') {
                return $this->positionsResponse();
            }

            if ($path === '/api/geofences' && $request->method() === 'POST') {
                return $this->createGeofenceResponse($request);
            }

            if (
                preg_match('#^/api/geofences/(\d+)$#', $path, $matches) === 1
            ) {
                $geofenceId = (int) $matches[1];

                return match ($request->method()) {
                    'GET' => $this->findGeofenceResponse($geofenceId),
                    'PUT' => $this->updateGeofenceResponse(
                        $geofenceId,
                        $request,
                    ),
                    'DELETE' => $this->deleteGeofenceResponse($geofenceId),
                    default => Http::response([], 405),
                };
            }

            if (
                $path === '/api/permissions'
                && in_array($request->method(), ['POST', 'DELETE'], true)
            ) {
                return Http::response(null, 204);
            }

            return Http::response([], 404);
        });
    }

    private function positionsResponse(): PromiseInterface
    {
        $devices = Device::query()
            ->whereNotNull('traccar_device_id')
            ->orderBy('id')
            ->get();

        $coordinates = [
            [45.8150, 15.9819],
            [45.8087, 15.9698],
            [45.8124, 15.9561],
            [45.8032, 15.9775],
            [45.7996, 15.9928],
            [45.8217, 15.9904],
            [45.8271, 15.9732],
            [45.8326, 15.9584],
            [45.8380, 15.9851],
            [45.8185, 16.0052],
            [45.8078, 16.0147],
            [45.7964, 16.0079],
            [45.7889, 15.9901],
            [45.7846, 15.9713],
            [45.7928, 15.9527],
            [45.8095, 15.9408],
            [45.8243, 15.9446],
            [45.8432, 15.9665],
            [45.8490, 15.9937],
            [45.8364, 16.0151],
            [45.8228, 16.0274],
            [45.8051, 16.0308],
            [45.7902, 16.0193],
            [45.7779, 15.9995],
            [45.7758, 15.9762],
            [45.7834, 15.9496],
            [45.7987, 15.9309],
            [45.8204, 15.9268],
            [45.8425, 15.9387],
            [45.8551, 15.9638],
        ];

        $positions = $devices
            ->values()
            ->map(function (Device $device, int $index) use ($coordinates): array {
                $traccarDeviceId = (int) $device->traccar_device_id;

                $fixTime = match ($index % 4) {
                    0 => now()->subMinutes(1),
                    1 => now()->subMinutes(2),
                    2 => now()->subMinutes(10),
                    default => now()->subMinutes(15),
                };

                $coordinate = $coordinates[
                    $index % count($coordinates)
                ];

                return [
                    'id' => 1000 + $traccarDeviceId,
                    'deviceId' => $traccarDeviceId,
                    'protocol' => 'osmand',
                    'serverTime' => now()->toIso8601String(),
                    'deviceTime' => $fixTime->toIso8601String(),
                    'fixTime' => $fixTime->toIso8601String(),
                    'outdated' => false,
                    'valid' => true,
                    'latitude' => $coordinate[0],
                    'longitude' => $coordinate[1],
                    'altitude' => 115 + (($index * 7) % 55),
                    'speed' => 5 + (($index * 7) % 45),
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
    }

    private function createGeofenceResponse(
        Request $request,
    ): PromiseInterface {
        $id = self::$nextGeofenceId++;

        $geofence = [
            'id' => $id,
            'attributes' => (object) [],
            'calendarId' => 0,
            'name' => (string) $request['name'],
            'description' => $request['description'],
            'area' => (string) $request['area'],
        ];

        self::$geofences[$id] = $geofence;

        return Http::response($geofence, 200);
    }

    private function findGeofenceResponse(int $geofenceId): PromiseInterface
    {
        $geofence = self::$geofences[$geofenceId] ?? null;

        if ($geofence === null) {
            return Http::response([
                'message' => 'Geofence not found.',
            ], 404);
        }

        return Http::response($geofence);
    }

    private function updateGeofenceResponse(
        int $geofenceId,
        Request $request,
    ): PromiseInterface {
        if (! isset(self::$geofences[$geofenceId])) {
            return Http::response([
                'message' => 'Geofence not found.',
            ], 404);
        }

        $geofence = [
            ...self::$geofences[$geofenceId],
            ...$request->data(),
            'id' => $geofenceId,
        ];

        self::$geofences[$geofenceId] = $geofence;

        return Http::response($geofence);
    }

    private function deleteGeofenceResponse(
        int $geofenceId,
    ): PromiseInterface {
        unset(self::$geofences[$geofenceId]);

        return Http::response(null, 204);
    }
}

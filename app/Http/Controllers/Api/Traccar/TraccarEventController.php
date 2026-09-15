<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Traccar;

use App\Actions\Traccar\HandleDeviceOfflineEvent;
use App\Actions\Traccar\HandleGeofenceEvent;
use App\Actions\Traccar\HandleIgnitionEvent;
use App\Actions\Traccar\HandleOverspeedEvent;
use App\Data\Traccar\DeviceOfflineEventData;
use App\Data\Traccar\GeofenceEventData;
use App\Data\Traccar\IgnitionEventData;
use App\Data\Traccar\OverspeedEventData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

final class TraccarEventController extends Controller
{
    public function __construct(
        private readonly HandleGeofenceEvent $handleGeofenceEvent,
        private readonly HandleOverspeedEvent $handleOverspeedEvent,
        private readonly HandleIgnitionEvent $handleIgnitionEvent,
        private readonly HandleDeviceOfflineEvent $handleDeviceOfflineEvent,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'type' => [
                'required',
                'string',
                Rule::in([
                    'geofenceEnter',
                    'geofenceExit',
                    'deviceOverspeed',
                    'ignitionOn',
                    'ignitionOff',
                    'deviceOffline',
                ]),
            ],
            'deviceId' => ['required', 'integer'],
            'eventTime' => ['required', 'date'],

            'geofenceId' => [
                Rule::requiredIf(
                    fn (): bool => in_array(
                        $request->input('type'),
                        ['geofenceEnter', 'geofenceExit'],
                        true,
                    ),
                ),
                'integer',
            ],

            'positionId' => [
                Rule::requiredIf(
                    fn (): bool => in_array(
                        $request->input('type'),
                        [
                            'deviceOverspeed',
                            'ignitionOn',
                            'ignitionOff',
                        ],
                        true,
                    ),
                ),
                'integer',
            ],

            'attributes' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('type')
                        === 'deviceOverspeed',
                ),
                'array',
            ],

            'attributes.speed' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('type')
                        === 'deviceOverspeed',
                ),
                'numeric',
            ],

            'attributes.speedLimit' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('type')
                        === 'deviceOverspeed',
                ),
                'numeric',
            ],
        ]);

        match ($validated['type']) {
            'geofenceEnter',
            'geofenceExit' => $this->handleGeofenceEvent->execute(
                GeofenceEventData::fromArray($validated),
            ),

            'deviceOverspeed' => $this->handleOverspeedEvent->execute(
                OverspeedEventData::fromArray($validated),
            ),

            'ignitionOn',
            'ignitionOff' => $this->handleIgnitionEvent->execute(
                IgnitionEventData::fromArray($validated),
            ),

            'deviceOffline' => $this->handleDeviceOfflineEvent->execute(
                DeviceOfflineEventData::fromArray($validated),
            ),

            default => throw new RuntimeException(
                'Unsupported validated Traccar event type.',
            ),
        };

        return response()->json([
            'message' => 'Event processed successfully.',
        ]);
    }
}

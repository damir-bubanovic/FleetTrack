<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Traccar;

use App\Actions\Traccar\HandleGeofenceEvent;
use App\Actions\Traccar\HandleOverspeedEvent;
use App\Data\Traccar\GeofenceEventData;
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
                    fn (): bool => $request->input('type')
                        === 'deviceOverspeed',
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

            default => throw new RuntimeException(
                'Unsupported validated Traccar event type.',
            ),
        };

        return response()->json([
            'message' => 'Event processed successfully.',
        ]);
    }
}

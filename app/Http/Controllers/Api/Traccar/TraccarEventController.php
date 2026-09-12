<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Traccar;

use App\Actions\Traccar\HandleGeofenceEvent;
use App\Data\Traccar\GeofenceEventData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TraccarEventController extends Controller
{
    public function __construct(
        private readonly HandleGeofenceEvent $handleGeofenceEvent,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'type' => [
                'required',
                'string',
                Rule::in(['geofenceEnter', 'geofenceExit']),
            ],
            'deviceId' => ['required', 'integer'],
            'geofenceId' => ['required', 'integer'],
            'eventTime' => ['required', 'date'],
        ]);

        $event = GeofenceEventData::fromArray($validated);

        $this->handleGeofenceEvent->execute($event);

        return response()->json([
            'message' => 'Event processed successfully.',
        ]);
    }
}

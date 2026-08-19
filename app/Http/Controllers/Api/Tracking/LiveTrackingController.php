<?php

namespace App\Http\Controllers\Api\Tracking;

use App\Actions\Tracking\GetLivePositions;
use App\Http\Controllers\Controller;
use App\Http\Resources\Tracking\LivePositionResource;
use App\Actions\Tracking\GetVehicleLivePosition;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;


class LiveTrackingController extends Controller
{
    public function __construct(
        private readonly GetLivePositions $getLivePositions,
        private readonly GetVehicleLivePosition $getVehicleLivePosition,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('tracking.view');

        /** @var User $user */
        $user = $request->user();

        $positions = $this->getLivePositions->handle($user);

        return LivePositionResource::collection($positions);
    }

    public function show(Request $request, Vehicle $vehicle): LivePositionResource
    {
        $this->authorize('tracking.view');

        /** @var User $user */
        $user = $request->user();

        $position = $this->getVehicleLivePosition->handle($user, $vehicle);

        abort_if($position === null, 404);

        return new LivePositionResource($position);
    }
}

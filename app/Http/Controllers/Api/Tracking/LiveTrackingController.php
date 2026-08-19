<?php

namespace App\Http\Controllers\Api\Tracking;

use App\Actions\Tracking\GetLivePositions;
use App\Actions\Tracking\GetVehicleLivePosition;
use App\Actions\Tracking\GetVehiclePositionHistory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tracking\LivePositionsRequest;
use App\Http\Requests\Tracking\VehiclePositionHistoryRequest;
use App\Http\Resources\Tracking\HistoricalPositionResource;
use App\Http\Resources\Tracking\LivePositionResource;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LiveTrackingController extends Controller
{
    public function __construct(
        private readonly GetLivePositions $getLivePositions,
        private readonly GetVehicleLivePosition $getVehicleLivePosition,
        private readonly GetVehiclePositionHistory $getVehiclePositionHistory,
    ) {}

    public function index(LivePositionsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('tracking.view');

        /** @var User $user */
        $user = $request->user();

        $fleetId = $request->integer('fleet_id') ?: null;
        $vehicleId = $request->integer('vehicle_id') ?: null;

        $positions = $this->getLivePositions->handle(
            $user,
            $fleetId,
            $vehicleId,
        );

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

    public function history(
        VehiclePositionHistoryRequest $request,
        Vehicle $vehicle,
    ): AnonymousResourceCollection {
        $this->authorize('tracking.view');

        /** @var User $user */
        $user = $request->user();

        $from = CarbonImmutable::parse($request->string('from')->toString());
        $to = CarbonImmutable::parse($request->string('to')->toString());

        $positions = $this->getVehiclePositionHistory->handle(
            $user,
            $vehicle,
            $from,
            $to,
        );

        return HistoricalPositionResource::collection($positions);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Report;

use App\Actions\Tracking\GetVehicleStops;
use App\Actions\Tracking\GetVehicleTrips;
use App\Actions\Tracking\GetVehicleTripSummary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\VehicleTripReportRequest;
use App\Http\Resources\Tracking\VehicleStopResource;
use App\Http\Resources\Tracking\VehicleTripResource;
use App\Http\Resources\Tracking\VehicleTripSummaryResource;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ReportController extends Controller
{
    public function __construct(
        private readonly GetVehicleTrips $getVehicleTrips,
        private readonly GetVehicleTripSummary $getVehicleTripSummary,
        private readonly GetVehicleStops $getVehicleStops,
    ) {}

    public function vehicleTrips(
        VehicleTripReportRequest $request,
        Vehicle $vehicle,
    ): AnonymousResourceCollection {
        $this->authorize('reports.view');

        /** @var User $user */
        $user = $request->user();

        $from = CarbonImmutable::parse(
            $request->string('from')->toString(),
        );

        $to = CarbonImmutable::parse(
            $request->string('to')->toString(),
        );

        $trips = $this->getVehicleTrips->handle(
            $user,
            $vehicle,
            $from,
            $to,
        );

        return VehicleTripResource::collection($trips);
    }

    public function vehicleTripSummary(
        VehicleTripReportRequest $request,
        Vehicle $vehicle,
    ): VehicleTripSummaryResource {
        $this->authorize('reports.view');

        /** @var User $user */
        $user = $request->user();

        $from = CarbonImmutable::parse(
            $request->string('from')->toString(),
        );

        $to = CarbonImmutable::parse(
            $request->string('to')->toString(),
        );

        $summary = $this->getVehicleTripSummary->handle(
            $user,
            $vehicle,
            $from,
            $to,
        );

        return new VehicleTripSummaryResource($summary);
    }

    public function vehicleStops(
        VehicleTripReportRequest $request,
        Vehicle $vehicle,
    ): AnonymousResourceCollection {
        $this->authorize('reports.view');

        /** @var User $user */
        $user = $request->user();

        $from = CarbonImmutable::parse(
            $request->string('from')->toString(),
        );

        $to = CarbonImmutable::parse(
            $request->string('to')->toString(),
        );

        $stops = $this->getVehicleStops->handle(
            $user,
            $vehicle,
            $from,
            $to,
        );

        return VehicleStopResource::collection($stops);
    }
}

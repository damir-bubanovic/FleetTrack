<?php

namespace App\Http\Controllers\Api\Vehicle;

use App\Actions\Vehicle\CreateVehicle;
use App\Actions\Vehicle\DeleteVehicle;
use App\Actions\Vehicle\UpdateVehicle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vehicle\StoreVehicleRequest;
use App\Http\Requests\Vehicle\UpdateVehicleRequest;
use App\Http\Resources\Vehicle\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class VehicleController extends Controller
{
    public function __construct(
        private readonly CreateVehicle $createVehicle,
        private readonly UpdateVehicle $updateVehicle,
        private readonly DeleteVehicle $deleteVehicle,
    ) {}

    /**
     * Display a listing of vehicles.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::query()
            ->visibleTo($request->user())
            ->latest()
            ->paginate();

        return VehicleResource::collection($vehicles);
    }

    /**
     * Store a newly created vehicle.
     */
    public function store(
        StoreVehicleRequest $request,
    ): VehicleResource {
        $this->authorize('create', Vehicle::class);

        $vehicle = $this->createVehicle->handle(
            $request->user(),
            $request->validated(),
        );

        return VehicleResource::make($vehicle);
    }

    /**
     * Display the specified vehicle.
     */
    public function show(
        Vehicle $vehicle,
    ): VehicleResource {
        $this->authorize('view', $vehicle);

        return VehicleResource::make($vehicle);
    }

    /**
     * Update the specified vehicle.
     */
    public function update(
        UpdateVehicleRequest $request,
        Vehicle $vehicle,
    ): VehicleResource {
        $this->authorize('update', $vehicle);

        $vehicle = $this->updateVehicle->handle(
            $request->user(),
            $vehicle,
            $request->validated(),
        );

        return VehicleResource::make($vehicle);
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(
        Vehicle $vehicle,
    ): Response {
        $this->authorize('delete', $vehicle);

        $this->deleteVehicle->handle($vehicle);

        return response()->noContent();
    }
}

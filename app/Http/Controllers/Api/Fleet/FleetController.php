<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Actions\Fleet\CreateFleet;
use App\Actions\Fleet\DeleteFleet;
use App\Actions\Fleet\UpdateFleet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreFleetRequest;
use App\Http\Requests\Fleet\UpdateFleetRequest;
use App\Http\Resources\Fleet\FleetResource;
use App\Models\Fleet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FleetController extends Controller
{
    public function __construct(
        private readonly CreateFleet $createFleet,
        private readonly UpdateFleet $updateFleet,
        private readonly DeleteFleet $deleteFleet,
    ) {
    }

    /**
     * Display a listing of fleets.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Fleet::class);

        $fleets = Fleet::query()
            ->latest()
            ->paginate();

        return FleetResource::collection($fleets);
    }

    /**
     * Store a newly created fleet.
     */
    public function store(StoreFleetRequest $request): FleetResource
    {
        $this->authorize('create', Fleet::class);

        $fleet = $this->createFleet->handle(
            $request->user(),
            $request->validated()
        );

        return FleetResource::make($fleet);
    }

    /**
     * Display the specified fleet.
     */
    public function show(Fleet $fleet): FleetResource
    {
        $this->authorize('view', $fleet);

        return FleetResource::make($fleet);
    }

    /**
     * Update the specified fleet.
     */
    public function update(
        UpdateFleetRequest $request,
        Fleet $fleet
    ): FleetResource {
        $this->authorize('update', $fleet);

        $fleet = $this->updateFleet->handle(
            $request->user(),
            $fleet,
            $request->validated()
        );

        return FleetResource::make($fleet);
    }

    /**
     * Remove the specified fleet.
     */
    public function destroy(Fleet $fleet): \Illuminate\Http\Response
    {
        $this->authorize('delete', $fleet);

        $this->deleteFleet->handle($fleet);

        return response()->noContent();
    }
}
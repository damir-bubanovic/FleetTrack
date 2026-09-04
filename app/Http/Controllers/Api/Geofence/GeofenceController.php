<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Geofence;

use App\Actions\Geofence\CreateGeofence;
use App\Actions\Geofence\DeleteGeofence;
use App\Actions\Geofence\UpdateGeofence;
use App\Http\Controllers\Controller;
use App\Http\Requests\Geofence\StoreGeofenceRequest;
use App\Http\Requests\Geofence\UpdateGeofenceRequest;
use App\Http\Resources\Geofence\GeofenceResource;
use App\Models\Geofence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GeofenceController extends Controller
{
    public function __construct(
        private readonly CreateGeofence $createGeofence,
        private readonly UpdateGeofence $updateGeofence,
        private readonly DeleteGeofence $deleteGeofence,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Geofence::class);

        $geofences = Geofence::query()
            ->visibleTo($request->user())
            ->latest()
            ->paginate();

        return GeofenceResource::collection($geofences);
    }

    public function store(StoreGeofenceRequest $request): GeofenceResource
    {
        $this->authorize('create', Geofence::class);

        $geofence = $this->createGeofence->handle(
            $request->user(),
            $request->validated()
        );

        return GeofenceResource::make($geofence);
    }

    public function show(Geofence $geofence): GeofenceResource
    {
        $this->authorize('view', $geofence);

        return GeofenceResource::make($geofence);
    }

    public function update(
        UpdateGeofenceRequest $request,
        Geofence $geofence
    ): GeofenceResource {
        $this->authorize('update', $geofence);

        $geofence = $this->updateGeofence->handle(
            $request->user(),
            $geofence,
            $request->validated()
        );

        return GeofenceResource::make($geofence);
    }

    public function destroy(Geofence $geofence): Response
    {
        $this->authorize('delete', $geofence);

        $this->deleteGeofence->handle($geofence);

        return response()->noContent();
    }
}

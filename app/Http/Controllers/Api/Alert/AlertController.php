<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Alert;

use App\Actions\Alert\AcknowledgeAlert;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AlertController extends Controller
{
    public function __construct(
        private readonly AcknowledgeAlert $acknowledgeAlert,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Alert::class);

        $alerts = Alert::query()
            ->visibleTo($request->user())
            ->latest('occurred_at')
            ->paginate();

        return AlertResource::collection($alerts);
    }

    public function show(Alert $alert): AlertResource
    {
        $this->authorize('view', $alert);

        return new AlertResource($alert);
    }

    public function acknowledge(
        Request $request,
        Alert $alert,
    ): AlertResource {
        $this->authorize('acknowledge', $alert);

        $alert = $this->acknowledgeAlert->execute(
            alert: $alert,
            user: $request->user(),
        );

        return new AlertResource($alert);
    }
}

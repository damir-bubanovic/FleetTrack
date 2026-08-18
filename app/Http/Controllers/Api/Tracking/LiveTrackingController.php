<?php

namespace App\Http\Controllers\Api\Tracking;

use App\Actions\Tracking\GetLivePositions;
use App\Http\Controllers\Controller;
use App\Http\Resources\Tracking\LivePositionResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LiveTrackingController extends Controller
{
    public function __construct(
        private readonly GetLivePositions $getLivePositions,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('tracking.view');

        /** @var User $user */
        $user = $request->user();

        $positions = $this->getLivePositions->handle($user);

        return LivePositionResource::collection($positions);
    }
}

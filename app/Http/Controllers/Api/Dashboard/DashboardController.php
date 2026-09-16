<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Dashboard;

use App\Actions\Dashboard\GetDashboardOverview;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\DashboardOverviewResource;
use App\Models\User;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly GetDashboardOverview $getDashboardOverview,
    ) {}

    public function overview(Request $request): DashboardOverviewResource
    {
        /** @var User $user */
        $user = $request->user();

        $overview = $this->getDashboardOverview->handle($user);

        return new DashboardOverviewResource($overview);
    }
}

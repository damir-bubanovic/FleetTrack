<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\AlertRule;

use App\Actions\AlertRule\CreateAlertRule;
use App\Actions\AlertRule\DeleteAlertRule;
use App\Actions\AlertRule\UpdateAlertRule;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlertRule\StoreAlertRuleRequest;
use App\Http\Requests\AlertRule\UpdateAlertRuleRequest;
use App\Http\Resources\AlertRuleResource;
use App\Models\AlertRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AlertRuleController extends Controller
{
    public function __construct(
        private readonly CreateAlertRule $createAlertRule,
        private readonly UpdateAlertRule $updateAlertRule,
        private readonly DeleteAlertRule $deleteAlertRule,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AlertRule::class);

        $alertRules = AlertRule::query()
            ->visibleTo(request()->user())
            ->latest()
            ->paginate();

        return AlertRuleResource::collection($alertRules);
    }

    public function store(
        StoreAlertRuleRequest $request,
    ): AlertRuleResource {
        $this->authorize('create', AlertRule::class);

        $alertRule = $this->createAlertRule->execute(
            $request->user(),
            $request->validated(),
        );

        return new AlertRuleResource($alertRule);
    }

    public function show(
        AlertRule $alertRule,
    ): AlertRuleResource {
        $this->authorize('view', $alertRule);

        return new AlertRuleResource($alertRule);
    }

    public function update(
        UpdateAlertRuleRequest $request,
        AlertRule $alertRule,
    ): AlertRuleResource {
        $this->authorize('update', $alertRule);

        $alertRule = $this->updateAlertRule->execute(
            $request->user(),
            $alertRule,
            $request->validated(),
        );

        return new AlertRuleResource($alertRule);
    }

    public function destroy(
        AlertRule $alertRule,
    ): JsonResponse {
        $this->authorize('delete', $alertRule);

        $this->deleteAlertRule->execute($alertRule);

        return response()->json(
            status: 204,
        );
    }
}

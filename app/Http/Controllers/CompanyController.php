<?php

namespace App\Http\Controllers;

use App\Actions\Company\CreateCompany;
use App\Actions\Company\UpdateCompany;
use App\Enums\UserRole;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Company::class);

        $user = $request->user();

        $companies = Company::query()
            ->where('slug', '!=', config('fleettrack.system_company_slug'))
            ->when(
                ! $user->hasRole(UserRole::SuperAdmin->value),
                fn (Builder $query): Builder => $query->whereKey($user->company_id),
            )
            ->latest()
            ->paginate();

        return CompanyResource::collection($companies);
    }

    /**
     * Store a newly created company.
     */
    public function store(
        StoreCompanyRequest $request,
        CreateCompany $action
    ): CompanyResource {
        $company = $action->handle(
            $request->validated()
        );

        return new CompanyResource($company);
    }

    /**
     * Display the specified company.
     */
    public function show(Company $company): CompanyResource
    {
        $this->authorize('view', $company);

        return new CompanyResource($company);
    }

    /**
     * Update the specified company.
     */
    public function update(
        UpdateCompanyRequest $request,
        Company $company,
        UpdateCompany $action
    ): CompanyResource {
        $company = $action->handle(
            $company,
            $request->validated()
        );

        return new CompanyResource($company);
    }

    /**
     * Remove the specified company.
     */
    public function destroy(Company $company): Response
    {
        $this->authorize('delete', $company);

        $company->delete();

        return response()->noContent();
    }
}
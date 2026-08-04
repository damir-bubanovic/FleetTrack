<?php

namespace App\Http\Controllers\Api\Driver;

use App\Actions\Driver\CreateDriver;
use App\Actions\Driver\DeleteDriver;
use App\Actions\Driver\UpdateDriver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverRequest;
use App\Http\Requests\Driver\UpdateDriverRequest;
use App\Http\Resources\Driver\DriverResource;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;



class DriverController extends Controller
{
    public function __construct(
        private readonly CreateDriver $createDriver,
        private readonly UpdateDriver $updateDriver,
        private readonly DeleteDriver $deleteDriver,
    ) {
    }


    /**
     * Display a listing of drivers.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Driver::class);

        $drivers = Driver::query()
            ->visibleTo($request->user())
            ->latest()
            ->paginate();

        return DriverResource::collection($drivers);
    }

    /**
     * Store a newly created driver.
     */
    public function store(StoreDriverRequest $request): DriverResource
    {
        $this->authorize('create', Driver::class);

        $driver = $this->createDriver->handle(
            $request->user(),
            $request->validated(),
        );

        return DriverResource::make($driver);
    }

    /**
     * Display the specified driver.
     */
    public function show(Driver $driver): DriverResource
    {
        $this->authorize('view', $driver);

        return DriverResource::make($driver);
    }

    /**
     * Update the specified driver.
     */
    public function update(
        UpdateDriverRequest $request,
        Driver $driver,
    ): DriverResource {
        $this->authorize('update', $driver);

        $driver = $this->updateDriver->handle(
            $request->user(),
            $driver,
            $request->validated(),
        );

        return DriverResource::make($driver);
    }

    /**
     * Remove the specified driver.
     */
    public function destroy(Driver $driver): Response
    {
        $this->authorize('delete', $driver);

        $this->deleteDriver->handle($driver);

        return response()->noContent();
    }
}
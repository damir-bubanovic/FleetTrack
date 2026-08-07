<?php

namespace App\Http\Controllers\Api\Device;

use App\Actions\Device\CreateDevice;
use App\Actions\Device\DeleteDevice;
use App\Actions\Device\UpdateDevice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Device\StoreDeviceRequest;
use App\Http\Requests\Device\UpdateDeviceRequest;
use App\Http\Resources\Device\DeviceResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DeviceController extends Controller
{
    public function __construct(
        private readonly CreateDevice $createDevice,
        private readonly UpdateDevice $updateDevice,
        private readonly DeleteDevice $deleteDevice,
    ) {}

    /**
     * Display a listing of devices.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Device::class);

        $devices = Device::query()
            ->visibleTo($request->user())
            ->latest()
            ->paginate();

        return DeviceResource::collection($devices);
    }

    /**
     * Store a newly created device.
     */
    public function store(
        StoreDeviceRequest $request,
    ): DeviceResource {
        $this->authorize('create', Device::class);

        $device = $this->createDevice->handle(
            $request->user(),
            $request->validated(),
        );

        return DeviceResource::make($device);
    }

    /**
     * Display the specified device.
     */
    public function show(
        Device $device,
    ): DeviceResource {
        $this->authorize('view', $device);

        return DeviceResource::make($device);
    }

    /**
     * Update the specified device.
     */
    public function update(
        UpdateDeviceRequest $request,
        Device $device,
    ): DeviceResource {
        $this->authorize('update', $device);

        $device = $this->updateDevice->handle(
            $request->user(),
            $device,
            $request->validated(),
        );

        return DeviceResource::make($device);
    }

    /**
     * Remove the specified device.
     */
    public function destroy(
        Device $device,
    ): Response {
        $this->authorize('delete', $device);

        $this->deleteDevice->handle($device);

        return response()->noContent();
    }
}

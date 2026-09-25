<?php

declare(strict_types=1);

use App\Actions\Device\UpdateDevice;
use App\Events\DeviceUpdated;
use App\Models\Fleet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesDevices;
use Tests\Traits\CreatesUsers;
use Tests\Traits\CreatesVehicles;

uses(
    RefreshDatabase::class,
    CreatesCompanies::class,
    CreatesUsers::class,
    CreatesVehicles::class,
    CreatesDevices::class,
);

test('device updated event preserves previous vehicle when device is reassigned', function (): void {
    Event::fake([DeviceUpdated::class]);

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $oldVehicle = $this->createVehicle($company, $fleet);
    $newVehicle = $this->createVehicle($company, $fleet);

    $device = $this->createDevice($company, $oldVehicle, [
        'traccar_device_id' => 123,
    ]);

    $user = $this->createCompanyAdmin($company);

    $updatedDevice = app(UpdateDevice::class)->handle(
        $user,
        $device,
        [
            'vehicle_id' => $newVehicle->id,
            'name' => $device->name,
            'unique_id' => $device->unique_id,
            'status' => $device->status->value,
        ],
    );

    expect($updatedDevice->vehicle_id)->toBe($newVehicle->id);

    Event::assertDispatched(
        DeviceUpdated::class,
        function (DeviceUpdated $event) use (
            $device,
            $oldVehicle,
            $newVehicle,
        ): bool {
            return $event->device->id === $device->id
                && $event->device->vehicle_id === $newVehicle->id
                && $event->previousVehicleId === $oldVehicle->id;
        },
    );
});

test('device updated event preserves previous vehicle when device is unassigned', function (): void {
    Event::fake([DeviceUpdated::class]);

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $device = $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 123,
    ]);

    $user = $this->createCompanyAdmin($company);

    $updatedDevice = app(UpdateDevice::class)->handle(
        $user,
        $device,
        [
            'vehicle_id' => null,
            'name' => $device->name,
            'unique_id' => $device->unique_id,
            'status' => $device->status->value,
        ],
    );

    expect($updatedDevice->vehicle_id)->toBeNull();

    Event::assertDispatched(
        DeviceUpdated::class,
        function (DeviceUpdated $event) use ($device, $vehicle): bool {
            return $event->device->id === $device->id
                && $event->device->vehicle_id === null
                && $event->previousVehicleId === $vehicle->id;
        },
    );
});

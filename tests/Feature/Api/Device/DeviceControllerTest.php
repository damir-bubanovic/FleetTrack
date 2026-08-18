<?php

use App\Enums\DeviceStatus;
use App\Jobs\SyncDeviceToTraccar;
use App\Models\Fleet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesDevices;
use Tests\Traits\CreatesUsers;
use Tests\Traits\CreatesVehicles;
use App\Jobs\UpdateDeviceInTraccar;


uses(
    RefreshDatabase::class,
    CreatesCompanies::class,
    CreatesUsers::class,
    CreatesVehicles::class,
    CreatesDevices::class,
);

test('super admin can list devices', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevices($company, $vehicle, 3);

    $this->actingAsSuperAdmin();

    $response = $this->getJson('/api/v1/devices');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('company admin only sees devices from own company', function (): void {

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetA = Fleet::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleA = $this->createVehicle($companyA, $fleetA);
    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->createDevices($companyA, $vehicleA, 3);
    $this->createDevices($companyB, $vehicleB, 2);

    $this->actingAsCompanyAdmin($companyA);

    $response = $this->getJson('/api/v1/devices');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $response->assertJsonMissing([
        'company_id' => $companyB->id,
    ]);
});

test('company admin can view own device', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $device = $this->createDevice($company, $vehicle);

    $this->actingAsCompanyAdmin($company);

    $response = $this->getJson("/api/v1/devices/{$device->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $device->id)
        ->assertJsonPath('data.company_id', $company->id);
});

test('company admin cannot view device from another company', function (): void {

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $device = $this->createDevice($companyB, $vehicleB);

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson("/api/v1/devices/{$device->id}")
        ->assertForbidden();
});

test('company admin can create device', function (): void {
    Queue::fake();

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $response = $this->postJson('/api/v1/devices', [
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'GPS Device 1',
        'unique_id' => 'IMEI123456789',
        'model' => 'Teltonika FMB920',
        'phone' => '+385911234567',
        'status' => DeviceStatus::ACTIVE->value,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.company_id', $company->id)
        ->assertJsonPath('data.vehicle_id', $vehicle->id)
        ->assertJsonPath('data.traccar_device_id', null)
        ->assertJsonPath('data.unique_id', 'IMEI123456789');

    $this->assertDatabaseHas('devices', [
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'traccar_device_id' => null,
        'unique_id' => 'IMEI123456789',
    ]);

    Queue::assertPushed(SyncDeviceToTraccar::class);
});

test('company admin cannot create device for another company vehicle', function (): void {

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $this->actingAsCompanyAdmin($companyA);

    $this->postJson('/api/v1/devices', [
        'company_id' => $companyB->id,
        'vehicle_id' => $vehicleB->id,
        'name' => 'Forbidden Device',
        'unique_id' => 'FORBIDDEN123',
        'status' => DeviceStatus::ACTIVE->value,
    ])->assertForbidden();

    $this->assertDatabaseMissing('devices', [
        'unique_id' => 'FORBIDDEN123',
    ]);
});

test('unique id must be unique', function (): void {

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'unique_id' => 'IMEI123456789',
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->postJson('/api/v1/devices', [
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'GPS Device',
        'unique_id' => 'IMEI123456789',
        'status' => DeviceStatus::ACTIVE->value,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'unique_id',
        ]);
});

test('company admin can update own device', function (): void {

    Queue::fake();

    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $device = $this->createDevice($company, $vehicle);

    $this->actingAsCompanyAdmin($company);

    $response = $this->putJson("/api/v1/devices/{$device->id}", [
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'Updated GPS Device',
        'unique_id' => $device->unique_id,
        'model' => 'Teltonika FMC130',
        'phone' => '+38598111222',
        'status' => DeviceStatus::INACTIVE->value,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated GPS Device')
        ->assertJsonPath('data.traccar_device_id', $device->traccar_device_id);

    $this->assertDatabaseHas('devices', [
        'id' => $device->id,
        'name' => 'Updated GPS Device',
        'traccar_device_id' => $device->traccar_device_id,
    ]);

    Queue::assertPushed(UpdateDeviceInTraccar::class);
});

test('company admin cannot update device from another company', function (): void {

    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle($companyB, $fleetB);

    $device = $this->createDevice($companyB, $vehicleB);

    $this->actingAsCompanyAdmin($companyA);

    $this->putJson("/api/v1/devices/{$device->id}", [
        'company_id' => $companyB->id,
        'vehicle_id' => $vehicleB->id,
        'name' => 'Hacked Device',
        'unique_id' => $device->unique_id,
        'status' => DeviceStatus::ACTIVE->value,
    ])->assertForbidden();
});
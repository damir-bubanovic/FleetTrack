<?php

declare(strict_types=1);

use App\Models\Fleet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

test('company admin can view trip report for own vehicle', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([
            [
                'deviceId' => 101,
                'driverUniqueId' => 'driver-123',
                'startTime' => '2026-08-18T08:00:00+00:00',
                'endTime' => '2026-08-18T09:00:00+00:00',
                'startLat' => 45.8150,
                'startLon' => 15.9819,
                'endLat' => 45.8250,
                'endLon' => 16.0000,
                'distance' => 12500.0,
                'duration' => 3600,
                'averageSpeed' => 18.5,
                'maxSpeed' => 32.0,
                'startAddress' => 'Start address',
                'endAddress' => 'End address',
            ],
        ], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/reports/vehicles/{$vehicle->id}/trips"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.device_id', 101)
        ->assertJsonPath('data.0.driver_id', 'driver-123')
        ->assertJsonPath(
            'data.0.started_at',
            '2026-08-18T08:00:00+00:00',
        )
        ->assertJsonPath(
            'data.0.ended_at',
            '2026-08-18T09:00:00+00:00',
        )
        ->assertJsonPath('data.0.start_latitude', 45.8150)
        ->assertJsonPath('data.0.start_longitude', 15.9819)
        ->assertJsonPath('data.0.end_latitude', 45.8250)
        ->assertJsonPath('data.0.end_longitude', 16)
        ->assertJsonPath('data.0.distance_km', 12.5)
        ->assertJsonPath('data.0.duration_seconds', 3600)
        ->assertJsonPath('data.0.average_speed', 18.5)
        ->assertJsonPath('data.0.max_speed', 32)
        ->assertJsonPath('data.0.speed_unit', 'knots')
        ->assertJsonPath(
            'data.0.start_address',
            'Start address',
        )
        ->assertJsonPath(
            'data.0.end_address',
            'End address',
        );
});

test('trip report sends vehicle and date range to traccar', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/reports/vehicles/{$vehicle->id}/trips"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )->assertOk();

    Http::assertSent(function ($request): bool {
        $path = parse_url(
            $request->url(),
            PHP_URL_PATH,
        );

        parse_str(
            (string) parse_url(
                $request->url(),
                PHP_URL_QUERY,
            ),
            $query,
        );

        return str_ends_with(
            (string) $path,
            '/reports/trips',
        )
            && ($query['deviceId'] ?? null) === '101'
            && ($query['from'] ?? null)
                === '2026-08-18T08:00:00.000000Z'
            && ($query['to'] ?? null)
                === '2026-08-18T10:00:00.000000Z';
    });
});

test('trip report returns empty collection when traccar has no trips', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => 101,
    ]);

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/reports/vehicles/{$vehicle->id}/trips"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('company admin cannot access trip report data for another company vehicle', function (): void {
    $companyA = $this->createCompany();
    $companyB = $this->createCompany();

    $fleetB = Fleet::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $vehicleB = $this->createVehicle(
        $companyB,
        $fleetB,
    );

    $this->createDevice($companyB, $vehicleB, [
        'traccar_device_id' => 202,
    ]);

    Http::fake();

    $this->actingAsCompanyAdmin($companyA);

    $this->getJson(
        "/api/v1/reports/vehicles/{$vehicleB->id}/trips"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )
        ->assertOk()
        ->assertJsonCount(0, 'data');

    Http::assertNothingSent();
});

test('trip report returns empty collection for vehicle without synced device', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->createDevice($company, $vehicle, [
        'traccar_device_id' => null,
    ]);

    Http::fake();

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/reports/vehicles/{$vehicle->id}/trips"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )
        ->assertOk()
        ->assertJsonCount(0, 'data');

    Http::assertNothingSent();
});

test('trip report validates required date range', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/reports/vehicles/{$vehicle->id}/trips"
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'from',
            'to',
        ]);
});

test('trip report requires to to be after from', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->actingAsCompanyAdmin($company);

    $this->getJson(
        "/api/v1/reports/vehicles/{$vehicle->id}/trips"
        .'?from=2026-08-18T10:00:00Z'
        .'&to=2026-08-18T08:00:00Z'
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');
});

test('trip report requires authentication', function (): void {
    $company = $this->createCompany();

    $fleet = Fleet::factory()->create([
        'company_id' => $company->id,
    ]);

    $vehicle = $this->createVehicle($company, $fleet);

    $this->getJson(
        "/api/v1/reports/vehicles/{$vehicle->id}/trips"
        .'?from=2026-08-18T08:00:00Z'
        .'&to=2026-08-18T10:00:00Z'
    )->assertUnauthorized();
});

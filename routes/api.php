<?php

use App\Http\Controllers\Api\Alert\AlertController;
use App\Http\Controllers\Api\AlertRule\AlertRuleController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Company\CompanyController;
use App\Http\Controllers\Api\Device\DeviceController;
use App\Http\Controllers\Api\Driver\DriverController;
use App\Http\Controllers\Api\Fleet\FleetController;
use App\Http\Controllers\Api\Geofence\GeofenceController;
use App\Http\Controllers\Api\Geofence\GeofenceVehicleController;
use App\Http\Controllers\Api\Report\ReportController;
use App\Http\Controllers\Api\Traccar\TraccarEventController;
use App\Http\Controllers\Api\Tracking\LiveTrackingController;
use App\Http\Controllers\Api\Vehicle\VehicleController;
use App\Http\Middleware\SetPermissionTeam;
use App\Http\Middleware\VerifyTraccarWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function (): void {

    Route::post('auth/login', [
        AuthController::class,
        'login',
    ]);

    Route::post('traccar/events', TraccarEventController::class)
        ->middleware(VerifyTraccarWebhook::class)
        ->name('traccar.events');

    Route::middleware([
        'auth:sanctum',
        SetPermissionTeam::class,
    ])->group(function (): void {

        Route::get('auth/me', [
            AuthController::class,
            'me',
        ]);

        Route::post('auth/logout', [
            AuthController::class,
            'logout',
        ]);

        Route::get('tracking/positions', [LiveTrackingController::class, 'index'])
            ->name('tracking.positions');

        Route::get('tracking/vehicles/{vehicle}', [LiveTrackingController::class, 'show'])
            ->name('tracking.vehicles.show');

        Route::get('tracking/vehicles/{vehicle}/positions', [LiveTrackingController::class, 'history'])
            ->name('tracking.vehicles.positions');

        Route::get('tracking/vehicles/{vehicle}/trip-summary', [LiveTrackingController::class, 'tripSummary'])
            ->name('tracking.vehicles.trip-summary');

        Route::get('tracking/vehicles/{vehicle}/trips', [LiveTrackingController::class, 'trips'])
            ->name('tracking.vehicles.trips');

        Route::get(
            'reports/vehicles/{vehicle}/trips',
            [ReportController::class, 'vehicleTrips'],
        )->name('reports.vehicles.trips');

        Route::get(
            'reports/vehicles/{vehicle}/trip-summary',
            [ReportController::class, 'vehicleTripSummary'],
        )->name('reports.vehicles.trip-summary');

        Route::get(
            'reports/vehicles/{vehicle}/stops',
            [ReportController::class, 'vehicleStops'],
        )->name('reports.vehicles.stops');

        Route::get(
            'reports/vehicles/{vehicle}/events',
            [ReportController::class, 'vehicleEvents'],
        )->name('reports.vehicles.events');

        Route::get(
            'reports/vehicles/{vehicle}/route',
            [ReportController::class, 'vehicleRoute'],
        )->name('reports.vehicles.route');

        Route::get(
            'reports/vehicles/{vehicle}/summary',
            [ReportController::class, 'vehicleSummary'],
        )->name('reports.vehicles.summary');

        Route::get(
            'reports/vehicles/{vehicle}/hours',
            [ReportController::class, 'vehicleHours'],
        )->name('reports.vehicles.hours');

        Route::get(
            'reports/vehicles/{vehicle}/combined',
            [ReportController::class, 'vehicleCombinedReport'],
        )->name('reports.vehicles.combined');

        Route::post(
            'geofences/{geofence}/vehicles/{vehicle}',
            [GeofenceVehicleController::class, 'store'],
        )->name('geofences.vehicles.store');

        Route::delete(
            'geofences/{geofence}/vehicles/{vehicle}',
            [GeofenceVehicleController::class, 'destroy'],
        )->name('geofences.vehicles.destroy');

        Route::get('alerts', [AlertController::class, 'index'])
            ->name('alerts.index');

        Route::get('alerts/{alert}', [AlertController::class, 'show'])
            ->name('alerts.show');

        Route::patch('alerts/{alert}/acknowledge', [
            AlertController::class,
            'acknowledge',
        ])->name('alerts.acknowledge');

        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('fleets', FleetController::class);
        Route::apiResource('drivers', DriverController::class);
        Route::apiResource('vehicles', VehicleController::class);
        Route::apiResource('devices', DeviceController::class);
        Route::apiResource('geofences', GeofenceController::class);

        Route::apiResource('alert-rules', AlertRuleController::class)
            ->parameters([
                'alert-rules' => 'alertRule',
            ]);
    });
});

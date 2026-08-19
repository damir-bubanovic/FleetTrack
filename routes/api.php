<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Company\CompanyController;
use App\Http\Controllers\Api\Device\DeviceController;
use App\Http\Controllers\Api\Driver\DriverController;
use App\Http\Controllers\Api\Fleet\FleetController;
use App\Http\Controllers\Api\Tracking\LiveTrackingController;
use App\Http\Controllers\Api\Vehicle\VehicleController;
use App\Http\Middleware\SetPermissionTeam;
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

        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('fleets', FleetController::class);
        Route::apiResource('drivers', DriverController::class);
        Route::apiResource('vehicles', VehicleController::class);
        Route::apiResource('devices', DeviceController::class);
    });
});

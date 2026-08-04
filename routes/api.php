<?php

use App\Http\Controllers\Api\Company\CompanyController;
use App\Http\Controllers\Api\Fleet\FleetController;
use App\Http\Controllers\Api\Driver\DriverController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('fleets', FleetController::class);
        Route::apiResource('drivers', DriverController::class);
    });

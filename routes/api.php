<?php

use App\Http\Controllers\CompanyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::apiResource('companies', CompanyController::class);
    });

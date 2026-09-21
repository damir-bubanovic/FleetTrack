<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/login', 'Auth/Login')->name('web.login');

Route::inertia('/', 'Dashboard')->name('web.dashboard');

Route::inertia('/fleets', 'Fleets/Index')->name('web.fleets.index');

Route::inertia('/vehicles', 'Vehicles/Index')->name('web.vehicles.index');

Route::inertia('/drivers', 'Drivers/Index')->name('web.drivers.index');

Route::inertia('/devices', 'Devices/Index')->name('web.devices.index');

Route::inertia('/tracking', 'Tracking/Index')->name('web.tracking.index');

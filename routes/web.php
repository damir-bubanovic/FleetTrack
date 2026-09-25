<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/login', 'Auth/Login')->name('web.login');

Route::inertia('/', 'Dashboard')->name('web.dashboard');

Route::inertia('/fleets', 'Fleets/Index')->name('web.fleets.index');

Route::inertia('/vehicles', 'Vehicles/Index')->name('web.vehicles.index');

Route::inertia('/drivers', 'Drivers/Index')->name('web.drivers.index');

Route::inertia('/devices', 'Devices/Index')->name('web.devices.index');

Route::inertia('/tracking', 'Tracking/Index')->name('web.tracking.index');

Route::inertia('/geofences', 'Geofences/Index')->name('web.geofences.index');

Route::inertia('/alert-rules', 'AlertRules/Index')->name('web.alert-rules.index');

Route::inertia('/alerts', 'Alerts/Index')->name('web.alerts.index');

Route::inertia('/reports', 'Reports/Index')->name('web.reports.index');

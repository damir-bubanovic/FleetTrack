<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/login', 'Auth/Login')->name('web.login');

Route::inertia('/', 'Dashboard')->name('web.dashboard');

Route::inertia('/fleets', 'Fleets/Index')->name('web.fleets.index');

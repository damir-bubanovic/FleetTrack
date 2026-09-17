<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Dashboard')->name('web.dashboard');

Route::inertia('/fleets', 'Fleets/Index')->name('web.fleets.index');
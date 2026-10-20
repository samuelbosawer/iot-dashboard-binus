<?php

use App\Http\Controllers\Api\SensorController;
use Illuminate\Support\Facades\Route;

// Read-only endpoints for the history table, chart ranges and CSV preview.
// The ESP32 no longer posts here: readings arrive over MQTT (php artisan mqtt:listen).
Route::prefix('sensor')->name('api.sensor.')->group(function () {
    Route::get('/', [SensorController::class, 'index'])->name('index');

    Route::get('latest', [SensorController::class, 'latest'])->name('latest');
    Route::get('history', [SensorController::class, 'history'])->name('history');
});

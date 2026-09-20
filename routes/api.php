<?php

use App\Http\Controllers\Api\SensorController;
use Illuminate\Support\Facades\Route;

Route::prefix('sensor')->name('api.sensor.')->group(function () {
    Route::post('/', [SensorController::class, 'store'])
        ->middleware('api.key')
        ->name('store');

    Route::get('latest', [SensorController::class, 'latest'])->name('latest');
    Route::get('history', [SensorController::class, 'history'])->name('history');
});

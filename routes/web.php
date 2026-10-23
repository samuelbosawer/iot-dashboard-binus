<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InfluxTestController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\PumpCommandController;
use App\Http\Controllers\SensorExportController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VisitorController::class, 'index'])->name('visitor');
Route::permanentRedirect('visitor', '/');
Route::get('prediksi', [PredictionController::class, 'visitor'])->name('prediction');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('visitor/export', SensorExportController::class)
    ->middleware('throttle:10,1')
    ->name('visitor.export');

// Connection check for the Docker InfluxDB; never exposed in production.
if (! app()->isProduction()) {
    Route::get('test-influx', InfluxTestController::class)->name('test-influx');
}

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/export', SensorExportController::class)->name('sensor.export');
    Route::get('dashboard/prediksi', [PredictionController::class, 'dashboard'])->name('dashboard.prediction');
    Route::post('dashboard/pump', PumpCommandController::class)
        ->middleware('throttle:20,1')
        ->name('pump.command');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::resource('users', UserController::class)
        ->except('show')
        ->middleware('can:manage-users');
});

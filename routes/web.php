<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\SensorExportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VisitorController::class, 'index'])->name('visitor');
Route::permanentRedirect('visitor', '/');
Route::get('prediksi', [PredictionController::class, 'visitor'])->name('prediction');
Route::get('visitor/export', SensorExportController::class)
    ->middleware('throttle:10,1')
    ->name('visitor.export');

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
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::resource('users', UserController::class)
        ->except('show')
        ->middleware('can:manage-users');
});

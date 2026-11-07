<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('login', [AuthController::class, 'login'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('change-password', [AuthController::class, 'showChangePassword'])
        ->name('password.change');
    Route::put('change-password', [AuthController::class, 'changePassword'])
        ->name('password.change.update');

    Route::post('logout', [AuthController::class, 'logout'])
        ->name('logout');
});

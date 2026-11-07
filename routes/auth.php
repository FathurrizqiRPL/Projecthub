<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('login', [AuthController::class, 'login'])
        ->name('login.store');

    Route::get('forgot-password', [AuthController::class, 'showForgotPassword'])
        ->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'sendPasswordResetLink'])
        ->name('password.email');

    Route::get('reset-password/{token}', [AuthController::class, 'showResetPassword'])
        ->name('password.reset');
    Route::post('reset-password/{token}', [AuthController::class, 'resetPassword'])
        ->name('password.reset.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', [AuthController::class, 'showVerificationNotice'])
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [AuthController::class, 'showConfirmPassword'])
        ->name('password.confirm');
    Route::post('confirm-password', [AuthController::class, 'confirmPassword'])
        ->name('password.confirm.store');

    Route::get('change-password', [AuthController::class, 'showChangePassword'])
        ->name('password.change');
    Route::put('change-password', [AuthController::class, 'changePassword'])
        ->name('password.change.update');

    Route::post('logout', [AuthController::class, 'logout'])
        ->name('logout');
});

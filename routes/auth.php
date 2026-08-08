<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForcedPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Public registration and password-reset routes are intentionally disabled.
| Only existing Admin and Staff users can log in.
|
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/password/change', [ForcedPasswordController::class, 'edit'])
        ->name('password.force.edit');

    Route::put('/password/change', [ForcedPasswordController::class, 'update'])
        ->name('password.force.update');

    Route::put('/password', [PasswordController::class, 'update'])
        ->middleware(['active', 'password.changed'])
        ->name('password.update');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

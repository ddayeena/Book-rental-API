<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Auth\AuthController;
use App\Http\Controllers\Api\v1\Auth\PasswordResetController;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');

    Route::post('login', [AuthController::class, 'login'])
        ->name('login')
        ->middleware('throttle:5,1');

    Route::prefix('password-reset')
        ->middleware('throttle:5,1')
        ->group(function () {
            Route::post('send', [PasswordResetController::class, 'sendCode'])
                ->name('password-reset.send');
            Route::post('verify', [PasswordResetController::class, 'verifyCode'])
                ->name('password-reset.verify');
            Route::post('reset', [PasswordResetController::class, 'resetPassword'])
                ->name('password-reset.reset');
        });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });
});

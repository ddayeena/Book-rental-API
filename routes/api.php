<?php

use App\Http\Controllers\Api\v1\Admin\BookController;
use App\Http\Controllers\Api\v1\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Auth\AuthController;
use App\Http\Controllers\Api\v1\Auth\PasswordResetController;
use App\Http\Controllers\Api\v1\AuthorController;

Route::prefix('v1')->group(function () {

    // Auth & Security
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])
        ->name('login')
        ->middleware('throttle:5,1');

    Route::prefix('email')->group(function () {

        Route::post('verify', [AuthController::class, 'verifyEmail'])
            ->name('email.verify')
            ->middleware('throttle:5,1');

        Route::post('resend', [AuthController::class, 'resendVerifyCode'])
            ->name('email.resend')
            ->middleware('throttle:3,1');
    });

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


    // Public API
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('authors', AuthorController::class)->only(['index', 'show']);


    // Protected API
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        
        // Admin Panel API
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
            Route::apiResource('authors', AuthorController::class)->except(['index', 'show']);

            Route::post('books/bulk-delete', [BookController::class, 'bulkDestroy'])->name('books.bulk-delete');
            Route::post('books/bulk-active', [BookController::class, 'bulkToggleActive'])->name('books.bulk-active');
            Route::post('books/bulk-price', [BookController::class, 'bulkUpdatePrice'])->name('books.bulk-price');
            Route::post('books/bulk-export', [BookController::class, 'bulkExport'])->name('books.bulk-export'); 
            Route::apiResource('books', BookController::class);
        });
    });
});

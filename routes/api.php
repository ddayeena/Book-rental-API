<?php

use App\Http\Controllers\Api\v1\Admin\BookController;
use App\Http\Controllers\Api\v1\BookController as PublicBookController;
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


    Route::prefix('books')->controller(PublicBookController::class)->group(function () {
        Route::get('', 'index')->name('books.index');
        Route::get('{id}', 'show')->name('books.show');
        Route::get('{book}/related', 'related')->name('books.related');
    });
    
    // Protected API
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');


        // Admin Panel API
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
            Route::apiResource('authors', AuthorController::class)->except(['index', 'show']);

            // Books
            Route::prefix('books')->controller(BookController::class)->group(function () {
                // Trash and Restore
                Route::get('trash', 'trash')->name('books.trash');
                Route::post('bulk-restore', 'bulkRestore')->name('books.bulk-restore');
                Route::post('bulk-force-delete', 'bulkForceDelete')->name('books.bulk-force-delete');
        
                // Bulk Actions
                Route::post('bulk-delete', 'bulkDestroy')->name('books.bulk-delete');
                Route::post('bulk-active', 'bulkToggleActive')->name('books.bulk-active');
                Route::post('bulk-price', 'bulkUpdatePrice')->name('books.bulk-price');
                Route::post('bulk-export', 'bulkExport')->name('books.bulk-export');
                Route::post('bulk-import', 'bulkImport')->name('books.bulk-import');
            });
            Route::apiResource('books', BookController::class)->withTrashed(['show', 'update']);
        });
    });
});

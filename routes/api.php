<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Auth\AuthController;
use App\Http\Controllers\Api\v1\Admin\BookController;
use App\Http\Controllers\Api\v1\BookController as PublicBookController;
use App\Http\Controllers\Api\v1\Admin\CategoryController;
use App\Http\Controllers\Api\v1\CategoryController as PublicCategoryController;
use App\Http\Controllers\Api\v1\Auth\PasswordResetController;
use App\Http\Controllers\Api\v1\AuthorController as PublicAuthorController;
use App\Http\Controllers\Api\v1\Admin\AuthorController;
use App\Http\Controllers\Api\v1\Admin\RentalController;
use App\Http\Controllers\Api\v1\Admin\UserController;
use App\Http\Controllers\Api\v1\RentalController as PublicRentalController;
use App\Http\Controllers\Api\v1\ReviewController as PublicReviewController;
use App\Http\Controllers\Api\v1\WebhookController;

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
    Route::apiResource('categories', PublicCategoryController::class)->only(['index', 'show']);
    Route::apiResource('authors', PublicAuthorController::class)->only(['index', 'show']);

    Route::get('rentals/dictionaries', [PublicRentalController::class, 'dictionaries'])->name('rentals.dictionaries');

    // Books
    Route::prefix('books')->controller(PublicBookController::class)->group(function () {
        Route::get('', 'index')->name('books.index');
        Route::get('{id}', 'show')->name('books.show');
        Route::get('{book}/related', 'related')->name('books.related');
    });

    // Liqpay Webhook
    Route::post('webhooks/liqpay', [WebhookController::class, 'liqpay'])->name('webhooks.liqpay');

    // Protected API
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('reviews', PublicReviewController::class)->except('show');

        // Rental
        Route::apiResource('rentals', PublicRentalController::class)->only('index', 'show', 'store');
        Route::patch('rentals/{id}/cancel', [PublicRentalController::class, 'cancel'])->name('rentals.cancel');
        Route::post('rentals/{rental}/pay-debt', [PublicRentalController::class, 'payDebt'])->name('rentals.pay-debt');

        // Admin Panel API
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::apiResource('categories', CategoryController::class);
            Route::apiResource('authors', AuthorController::class);

            Route::prefix('users')->controller(UserController::class)->group(function () {
                Route::post('{user}/block', 'block')->name('users.block');
                Route::post('{user}/unblock', 'unblock')->name('users.unblock');
                Route::post('{user}/change-role', 'changeRole')->name('users.change-role');
                Route::post('{user}/restore', 'restore')->name('users.restore')->withTrashed();
            });
            Route::apiResource('users', UserController::class)->withTrashed(['show']);

            // Rentals
            Route::prefix('rentals')->controller(RentalController::class)->group(function () {
                Route::post('bulk-export', 'bulkExport')->name('rentals.bulk-export');
                Route::post('{rental}/restore', 'restore')->name('rentals.restore')->withTrashed();
                Route::post('{rental}/issue', 'issue')->name('rentals.issue');
                Route::post('{rental}/return', 'processReturn')->name('rentals.return');
                Route::post('{rental}/lost', 'markLost')->name('rentals.lost');
                Route::post('{rental}/mark-paid', 'markPaid')->name('rentals.mark-paid');
                Route::post('{rental}/cancel', 'cancel')->name('rentals.admin.cancel');
                Route::post('{rental}/mark-refunded', 'markRefunded')->name('rentals.mark-refunded');
            });
            Route::apiResource('rentals', RentalController::class)->withTrashed(['show', 'update']);

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

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillsController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\NotificationController;
use Laravel\Passport\Http\Middleware\CheckToken;
use App\Http\Controllers\BillCategoriesController;
use App\Http\Controllers\MonthlyCategorySummaryController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware('signed')
            ->name('verification.verify');
        Route::post('/send-verification-email', [AuthController::class, 'sendVerificationEmail']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('guest')->name('password.email');
        Route::post('/reset-password/{token}', [AuthController::class, 'resetPassword'])->middleware('guest')->name('password.reset');
        // for social auth
        Route::group(['middleware' => ['web']], function () {
            Route::get('google/redirect', [AuthController::class, 'handleSocialRedirect']);
            Route::get('google/callback', [AuthController::class, 'handleSocialAuthorize']);
        });
    });
    Route::post('/payments/webhook/xendit/checkout', [PaymentsController::class, 'webhook']);
    Route::middleware('auth:api')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
        Route::middleware([CheckToken::using('user:bill:crud', 'user:payment:crud', 'user:notification:r'), 'verified'])->group(function () {
            Route::prefix('auth')->group(function () {
                Route::post('/change-password', [AuthController::class, 'changePassword']);
            });
            Route::prefix('bills')->group(function () {
                Route::prefix('series')->group(function () {
                    Route::get('/', [BillsController::class, 'getRecurringSeries']);
                    Route::post('/', [BillsController::class, 'storeBillSeries']);
                    Route::get('/{id}', [BillsController::class, 'detailBillSeries']);
                    Route::put('/{id}', [BillsController::class, 'updateBillSeries']);
                    Route::delete('/{id}', [BillsController::class, 'deleteBillSeries']);
                });
                Route::prefix('categories')->group(function () {
                    Route::get('/', [BillCategoriesController::class, 'getBillCategories']);
                    Route::post('/', [BillCategoriesController::class, 'create']);
                    Route::get('/{id}', [BillCategoriesController::class, 'detail']);
                    Route::put('/{id}', [BillCategoriesController::class, 'update']);
                    Route::delete('/{id}', [BillCategoriesController::class, 'delete']);
                });
                Route::get('/', [BillsController::class, 'getUpcomingBills']);
                Route::post('/', [BillsController::class, 'storeBill']);
                Route::get('/{id}', [BillsController::class, 'detailBill']);
                Route::put('/{id}', [BillsController::class, 'updateBill']);
                Route::delete('/{id}', [BillsController::class, 'deleteBill']);
            });
            Route::prefix('payments')->group(function () {
                Route::post('/checkout', [PaymentsController::class, 'checkout']);
                Route::get('/', [PaymentsController::class, 'index']);
                Route::post('/', [PaymentsController::class, 'store']);
                Route::get('/{id}', [PaymentsController::class, 'detail']);
                Route::put('/{id}', [PaymentsController::class, 'update']);
                Route::delete('/{id}', [PaymentsController::class, 'delete']);
            });
            Route::prefix('notification')->group(function () {
                Route::get('/', [NotificationController::class, 'getAllNotificationUser']);
                Route::post('/{readId}', [NotificationController::class, 'readNotification']);
            });
            Route::prefix('media')->group(function () {
                Route::post('/upload', [MediaController::class, 'store']);
            });
            Route::prefix('analytics')->group(function () {
                Route::get('/spending-by-category', [MonthlyCategorySummaryController::class, 'getSpendingCountByCategory']);
                Route::get('/monthly-spending-trend', [MonthlyCategorySummaryController::class, 'getMonthlySpendingTrend']);
            });
        });
        Route::middleware([CheckToken::using('admin:notification:crud', 'admin:user:crud'), 'verified'])->group(function () {
            Route::prefix('notification')->group(function () {
                Route::get('/admin', [NotificationController::class, 'getAllNotificationAdminPublic']);
                Route::post('/admin', [NotificationController::class, 'store']);
                Route::put('/admin/{id}', [NotificationController::class, 'update']);
                Route::delete('/admin/{id}', [NotificationController::class, 'delete']);
            });
        });
    });
});

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillCategoriesController;
use App\Http\Controllers\BillsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentsController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
    });
    Route::middleware('auth:api')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
        Route::middleware(['auth:api', CheckToken::using('user:bill:crud', 'user:payment:crud', 'user:notification:r')])->group(function () {
            Route::prefix('bills')->group(function () {
                Route::get('/', [BillsController::class, 'index']);
                Route::post('/', [BillsController::class, 'store']);
                Route::get('/{id}', [BillsController::class, 'detail']);
                Route::put('/{id}', [BillsController::class, 'update']);
                Route::delete('/{id}', [BillsController::class, 'delete']);
                Route::prefix('categories')->group(function () {
                    Route::get('/', [BillCategoriesController::class, 'getBillCategories']);
                    Route::post('/', [BillCategoriesController::class, 'create']);
                    Route::get('/{id}', [BillCategoriesController::class, 'detail']);
                    Route::put('/{id}', [BillCategoriesController::class, 'update']);
                    Route::delete('/{id}', [BillCategoriesController::class, 'delete']);
                });
            });
            Route::prefix('payments')->group(function () {
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
        });
        Route::middleware(['auth:api', CheckToken::using('admin:notification:crud', 'admin:user:crud')])->group(function () {
            Route::prefix('notification')->group(function () {
                Route::get('/admin', [NotificationController::class, 'getAllNotificationAdminPublic']);
                Route::post('/admin', [NotificationController::class, 'store']);
                Route::put('/admin/{id}', [NotificationController::class, 'update']);
                Route::delete('/admin/{id}', [NotificationController::class, 'delete']);
            });
        });
    });
});

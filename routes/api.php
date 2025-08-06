<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillCategoriesController;
use App\Http\Controllers\BillsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationReadController;
use App\Http\Controllers\NotificationTypeController;
use App\Http\Controllers\PaymentsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
    });
    Route::middleware('auth:api')->group(function () {
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
            Route::prefix('types')->group(function () {
                Route::get('/', [NotificationTypeController::class, 'index']);
                Route::post('/', [NotificationTypeController::class, 'store']);
                Route::get('/{id}', [NotificationTypeController::class, 'detail']);
                Route::put('/{id}', [NotificationTypeController::class, 'update']);
                Route::delete('/{id}', [NotificationTypeController::class, 'delete']);
            });
            Route::prefix('reads')->group(function () {
                Route::get('/', [NotificationReadController::class, 'index']);
                Route::post('/', [NotificationReadController::class, 'store']);
                Route::get('/{id}', [NotificationReadController::class, 'detail']);
                Route::put('/{id}', [NotificationReadController::class, 'update']);
                Route::delete('/{id}', [NotificationReadController::class, 'delete']);
            });
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/', [NotificationController::class, 'store']);
            Route::get('/{id}', [NotificationController::class, 'detail']);
            Route::put('/{id}', [NotificationController::class, 'update']);
            Route::delete('/{id}', [NotificationController::class, 'delete']);
        });
    });
});

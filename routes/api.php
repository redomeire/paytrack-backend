<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillCategoriesController;
use App\Http\Controllers\BillsController;
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
    });
});

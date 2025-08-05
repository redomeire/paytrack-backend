<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillCategoriesController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
    });
    Route::prefix('bill')->group(function () {
           Route::prefix('categories')->group(function () {
                Route::get('/', [BillCategoriesController::class, 'getBillCategories']);
                Route::post('/', [BillCategoriesController::class, 'create']);
                Route::get('/{id}', [BillCategoriesController::class, 'detail']);
                Route::put('/{id}', [BillCategoriesController::class, 'update']);
                Route::delete('/{id}', [BillCategoriesController::class, 'delete']);
            });
        })->middleware('auth:api');
});
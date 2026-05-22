<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\HealthCheckController;

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');
});

Route::middleware('auth:api')->group(function () {
    Route::get('/protected-endpoint', function () {
        return response()->json(['message' => 'anda berhasil masuk']);
    });
});

Route::middleware('auth:api')->prefix('v1')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::patch('/products/{id}', [ProductController::class, 'update']);
    Route::get('/health', [HealthCheckController::class,])->name('api.health');
});


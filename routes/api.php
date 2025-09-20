<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\CacheController;
use App\Http\Middleware\APIKeyMiddleware;
use App\Http\Middleware\LeadRateLimitMiddleware;

Route::prefix('v1')->group(function () {
    Route::prefix('cars')->group(function () {
        Route::get('/', [CarController::class, 'index']);
        Route::get('/{id}', [CarController::class, 'show']);
    });
    Route::post('leads', [LeadController::class, 'store'])->middleware(LeadRateLimitMiddleware::class);
    Route::post('/admin/cache/purge', [CacheController::class, 'purge'])->middleware(APIKeyMiddleware::class);
});
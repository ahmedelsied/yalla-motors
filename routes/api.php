<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CarController;

Route::prefix('v1')->middleware('cache.headers:public;max_age=2628000;etag')->group(function () {
    Route::get('/cars', [CarController::class, 'index']);
    Route::get('/cars/{id}', [CarController::class, 'show']);
});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;


Route::prefix('v1')->group(function () {
    # admin registration
    Route::prefix('admin')->group(function () {
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('register', [AdminAuthController::class, 'register']);
        });
        Route::post('login', [AdminAuthController::class, 'login']);
        Route::post('logout', [AdminAuthController::class, 'logout']);
    });
});

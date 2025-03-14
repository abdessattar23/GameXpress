<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;

Route::prefix('v1')->group(function () {
    Route::prefix('admin')->group(function () {
        # Public routes
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('register', [AdminAuthController::class, 'register']);
        });
        Route::post('login', [AdminAuthController::class, 'login'])->name('login');

        # Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AdminAuthController::class, 'logout']);

            # Dashboard
            Route::get('dashboard', [AdminAuthController::class, 'dashboard'])
                ->middleware('permission:view_dashboard');

            # Product management routes
            Route::prefix('products')->group(function () {
                Route::get('/', [ProductController::class, 'index'])
                    ->middleware('permission:view_products');
                Route::get('/{id}', [ProductController::class, 'show'])
                    ->middleware('permission:view_products');
                Route::post('/', [ProductController::class, 'store'])
                    ->middleware('permission:create_products');
                Route::put('/{id}', [ProductController::class, 'update'])
                    ->middleware('permission:edit_products');
                Route::delete('/{id}', [ProductController::class, 'destroy'])
                    ->middleware('permission:delete_products');
            });

            # Category management routes
            Route::prefix('categories')->group(function () {
                Route::get('/', [CategoryController::class, 'index'])
                    ->middleware('permission:view_categories');
                Route::post('/', [CategoryController::class, 'store'])
                    ->middleware('permission:create_categories');
                Route::put('/{id}', [CategoryController::class, 'update'])
                    ->middleware('permission:edit_categories');
                Route::delete('/{id}', [CategoryController::class, 'destroy'])
                    ->middleware('permission:delete_categories');
            });

            # User management routes
            Route::prefix('users')->group(function () {
                Route::get('/', [UserController::class, 'index'])
                    ->middleware('permission:view_users');
                Route::post('/', [UserController::class, 'store'])
                    ->middleware('permission:create_users');
                Route::put('/{id}', [UserController::class, 'update'])
                    ->middleware('permission:edit_users');
                Route::delete('/{id}', [UserController::class, 'destroy'])
                    ->middleware('permission:delete_users');
            });
        });
    });
});

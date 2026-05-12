<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\StoreController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout')->middleware('auth:api');
    Route::post('refresh', 'refresh')->middleware('auth:api');
    Route::get('me', 'me')->middleware('auth:api');
    Route::put('profile', 'updateProfile')->middleware('auth:api');
});

// Public routes
Route::get('menu-items', [MenuItemController::class, 'index']);
Route::get('menu-items/{menu_item}', [MenuItemController::class, 'show']);
Route::get('stores', [StoreController::class, 'index']);
Route::get('stores/{store}', [StoreController::class, 'show']);

use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PushNotificationController;


// Protected routes
Route::middleware('auth:api')->group(function () {
    // Menu items (Write operations)
    Route::post('menu-items', [MenuItemController::class, 'store']);
    Route::put('menu-items/{menu_item}', [MenuItemController::class, 'update']);
    Route::patch('menu-items/{menu_item}', [MenuItemController::class, 'update']);
    Route::delete('menu-items/{menu_item}', [MenuItemController::class, 'destroy']);
    
    // Settings
    Route::patch('settings/operational', [SettingsController::class, 'updateOperational']);
    Route::patch('settings/password', [SettingsController::class, 'updatePassword']);
    
    // Orders & Queues
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('queues', QueueController::class);
    
    // Push Notifications
    Route::post('push/subscribe', [PushNotificationController::class, 'subscribe']);
    Route::post('push/unsubscribe', [PushNotificationController::class, 'unsubscribe']);

    // Stores (Management)
    Route::put('stores/{store}', [StoreController::class, 'update']);
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\RajaOngkirController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/locations', [RajaOngkirController::class, 'searchLocation']); 

Route::get('/products', [ProductController::class, 'index']);
Route::post('/webhooks/xendit', [WebhookController::class, 'handle']);

// PROTECTED ROUTES (Harus Login / Punya Token)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {return $request->user();});
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);

    // Product (Seller Only)
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('/seller/products', [ProductController::class, 'myProducts']);

    // Cart (Buyer Only)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/item/{itemId}', [CartController::class, 'update']);
    Route::delete('/cart/item/{itemId}', [CartController::class, 'destroy']);

    // RajaOngkir
    Route::post('/check-ongkir', [RajaOngkirController::class, 'checkOngkir']);

    // Order & Checkout
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);

});
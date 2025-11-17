<?php

use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/shipping/provinces', [ShippingController::class, 'provinces']);
Route::get('/shipping/cities/{provinceId}', [ShippingController::class, 'cities']);
Route::get('/shipping/districts/{cityId}', [ShippingController::class, 'districts']);
Route::get('/shipping/subdistricts/{districtId}', [ShippingController::class, 'subdistricts']);
Route::post('/shipping/cost', [ShippingController::class, 'cost']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/checkout', CheckoutController::class);
    Route::post('/orders/{id}/upload-proof', [PaymentController::class, 'uploadProof']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'can:admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::post('/products/{productId}/batches', [BatchController::class, 'store']);
    Route::get('/products/{productId}/batches', [BatchController::class, 'index']);
    Route::post('/products/{productId}/price-tiers', [ProductController::class, 'addPriceTier']);

    Route::get('/admin/orders', [AdminOrderController::class, 'index']);
    Route::get('/admin/orders/{id}', [AdminOrderController::class, 'show']);
    Route::post('/admin/orders/{id}/verify-payment', [AdminOrderController::class, 'verifyPayment']);
    Route::post('/admin/orders/{id}/ship', [AdminOrderController::class, 'ship']);
    Route::post('/admin/run-reservation-release', [AdminOrderController::class, 'runReservationRelease']);

    Route::get('/reports/batch-stock', [ReportController::class, 'batchStock']);
    Route::get('/reports/batch-sales', [ReportController::class, 'batchSales']);
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplacePipeline\Http\Controllers\AdminOrderController;
use Modules\MarketplacePipeline\Http\Controllers\AdminPayoutController;
use Modules\MarketplacePipeline\Http\Controllers\CartController;
use Modules\MarketplacePipeline\Http\Controllers\OrderController;
use Modules\MarketplacePipeline\Http\Controllers\PartnerOrderController;
use Modules\MarketplacePipeline\Http\Controllers\PartnerPayoutController;
use Modules\MarketplacePipeline\Http\Controllers\PaymentController;

Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store')->middleware('throttle:checkout');
    Route::patch('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::post('/paypal/store', [PaymentController::class, 'store'])->name('paypal.store')->middleware('throttle:checkout');
    Route::get('/paypal/capture', [PaymentController::class, 'capture'])->name('paypal.capture');
    Route::get('/paypal/cancel', function () {
        return redirect()->route('orders.index')->withErrors('Payment cancelled');
    })->name('paypal.cancel');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/complete', [AdminOrderController::class, 'complete'])->name('orders.complete');

    Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/{id}/process', [AdminPayoutController::class, 'process'])->name('payouts.process');
});

Route::prefix('partner')->middleware(['auth', 'partner'])->name('partner.')->group(function () {
    Route::patch('/orders/{id}/ship', [PartnerOrderController::class, 'ship'])->name('orders.ship');
    Route::get('/orders', [PartnerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [PartnerOrderController::class, 'show'])->name('orders.show');

    Route::get('/payouts', [PartnerPayoutController::class, 'index'])->name('payouts.index');
});
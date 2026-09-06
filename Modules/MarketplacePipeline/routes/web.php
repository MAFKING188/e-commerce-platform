<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplacePipeline\Http\Controllers\AdminOrderController;
use Modules\MarketplacePipeline\Http\Controllers\AdminPayoutController;
use Modules\MarketplacePipeline\Http\Controllers\CartController;
use Modules\MarketplacePipeline\Http\Controllers\OrderController;
use Modules\MarketplacePipeline\Http\Controllers\PartnerOrderController;
use Modules\MarketplacePipeline\Http\Controllers\PartnerPayoutController;
use Modules\MarketplacePipeline\Http\Controllers\PaymentController;

// Public: shared cart view (no auth required)
Route::get('/cart/shared/{token}', [CartController::class, 'showShared'])->name('cart.shared');

Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/clone/{token}', [CartController::class, 'cloneShared'])->name('cart.clone');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}/confirmation', [OrderController::class, 'confirmation'])->name('orders.confirmation');
    Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store')->middleware('throttle:checkout');
    Route::patch('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::post('/paypal/store', [PaymentController::class, 'store'])->name('paypal.store')->middleware('throttle:checkout');
    Route::get('/paypal/capture', [PaymentController::class, 'capture'])->name('paypal.capture');
    Route::get('/paypal/cancel', function () {
        return redirect()->route('orders.index')->withErrors('Payment cancelled');
    })->name('paypal.cancel');

    // Bank Transfer
    Route::post('/bank-transfer/store', [PaymentController::class, 'storeBankTransfer'])->name('bank-transfer.store');
    Route::get('/orders/{order}/upload-proof', [PaymentController::class, 'uploadProof'])->name('upload-proof');
    Route::post('/orders/{order}/upload-proof', [PaymentController::class, 'handleUploadProof'])->name('handle-upload-proof');
    
    // Per-payment proof upload (for bank transfer)
    Route::get('/payments/{payment}/upload-proof', [PaymentController::class, 'uploadProofPayment'])->name('payment.upload-proof');
    Route::post('/payments/{payment}/upload-proof', [PaymentController::class, 'handleUploadProofPayment'])->name('payment.handle-upload-proof');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/complete', [AdminOrderController::class, 'complete'])->name('orders.complete');

    Route::post('/admin/orders/{id}/validate-payment', [AdminOrderController::class, 'validatePayment'])->name('admin.orders.validate-payment');

    Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/{id}/process', [AdminPayoutController::class, 'process'])->name('payouts.process');
});

Route::prefix('partner')->middleware(['auth', 'partner'])->name('partner.')->group(function () {
    Route::patch('/orders/{id}/ship', [PartnerOrderController::class, 'ship'])->name('orders.ship');
    Route::patch('/orders/{id}/complete', [PartnerOrderController::class, 'complete'])->name('orders.complete');
    Route::patch('/orders/{id}/validate-payment', [PartnerOrderController::class, 'validatePayment'])->name('orders.validate-payment');
    Route::patch('/payments/{payment}/validate', [PartnerOrderController::class, 'validatePaymentForPayment'])->name('payments.validate');
    Route::get('/orders', [PartnerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [PartnerOrderController::class, 'show'])->name('orders.show');

    Route::get('/payouts', [PartnerPayoutController::class, 'index'])->name('payouts.index');
});
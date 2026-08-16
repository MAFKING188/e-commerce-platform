<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AdminOrderController,
    CartController,
    OrderController,
    PaymentController
};
use Modules\CatalogDelivery\Http\Controllers\ReviewController;

/*
|--------------------------------------------------------------------------
| Protected Member Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    
    /* Cart System */
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

    /* Order System */
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
    Route::patch('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    /* Payment System (PayPal) */
    Route::post('/paypal/store', [PaymentController::class, 'store'])->name('paypal.store');
    Route::get('/paypal/capture', [PaymentController::class, 'capture'])->name('paypal.capture');
    Route::get('/paypal/cancel', function () {
        return redirect()->route('orders.index')->withErrors('Payment cancelled');
    })->name('paypal.cancel');

    /* Reviews */
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});

/*
|--------------------------------------------------------------------------
| Administrative Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
    /* Order Management */
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/complete', [AdminOrderController::class, 'complete'])->name('orders.complete');

    /* Payout Management */
    Route::get('/payouts', [\App\Http\Controllers\AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/{id}/process', [\App\Http\Controllers\AdminPayoutController::class, 'process'])->name('payouts.process');
});

/*
|--------------------------------------------------------------------------
| Artisan Routes
|--------------------------------------------------------------------------
*/
    Route::middleware(['auth', 'partner'])->prefix('partner')->as('partner.')->group(function () {
        Route::get('payouts', [\App\Http\Controllers\PartnerPayoutController::class, 'index'])->name('payouts.index');
        Route::resource('orders', \App\Http\Controllers\PartnerOrderController::class)->only(['index', 'show']);
    });

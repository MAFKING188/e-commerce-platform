<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AdminOrderController,
    CartController,
    CategoryController,
    OrderController,
    PaymentController,
    ProductController,
    ReviewController,
    PartnerController,
    ViewController
};

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [ViewController::class, 'home'])->name('home');
Route::get('/shop', [ViewController::class, 'shop'])->name('shop');
Route::get('/product/{id}', [ViewController::class, 'product'])->name('product.show');
Route::get('/artisan-profile/{id}', [ViewController::class, 'partnerProfile'])->name('partner.profile');
Route::get('/about', [ViewController::class, 'about'])->name('about');
Route::get('/contact', [ViewController::class, 'contact'])->name('contact');

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
    Route::post('/reviews', [\App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');
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

    Route::post('/products/{product}/reorder-images', [\App\Http\Controllers\ProductController::class, 'reorderImages'])->name('products.reorder-images');
    Route::delete('/products/{product}/images/{image}', [\App\Http\Controllers\ProductController::class, 'deleteImage'])->name('products.delete-image');
    Route::resource('products', \App\Http\Controllers\ProductController::class)->except(['show'])->names([
        'index' => 'products.index',
        'create' => 'products.create',
        'store' => 'products.store',
        'edit' => 'products.edit',
        'update' => 'products.update',
        'destroy' => 'products.destroy',
    ]);
    Route::resource('categories', \App\Http\Controllers\CategoryController::class)->names([
        'index' => 'categories.index',
        'create' => 'categories.create',
        'store' => 'categories.store',
        'edit' => 'categories.edit',
        'update' => 'categories.update',
        'destroy' => 'categories.destroy',
    ]);
    Route::resource('partners', \App\Http\Controllers\PartnerController::class)->names([
        'index' => 'partners.index',
        'create' => 'partners.create',
        'store' => 'partners.store',
        'show' => 'partners.show',
        'edit' => 'partners.edit',
        'update' => 'partners.update',
        'destroy' => 'partners.destroy',
    ]);
    
    /* Community Moderation */
    Route::get('/reviews', [\App\Http\Controllers\ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{id}/approve', [\App\Http\Controllers\ReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{id}/reject', [\App\Http\Controllers\ReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{id}', [\App\Http\Controllers\ReviewController::class, 'destroy'])->name('reviews.destroy');

    /* Payout Management */
    Route::get('/payouts', [\App\Http\Controllers\AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/{id}/process', [\App\Http\Controllers\AdminPayoutController::class, 'process'])->name('payouts.process');

    Route::post('/partners/{id}/add-product', [\App\Http\Controllers\PartnerController::class, 'addProduct'])->name('partners.add_product');
    Route::delete('/partners/{id}/remove-product/{productId}', [\App\Http\Controllers\PartnerController::class, 'removeProduct'])->name('partners.remove_product');
});

/*
|--------------------------------------------------------------------------
| Artisan Routes
|--------------------------------------------------------------------------
*/
    Route::middleware(['auth', 'partner'])->prefix('partner')->as('partner.')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\PartnerDashboardController::class, 'index'])->name('dashboard');
        
        Route::post('inventory/bulk-action', [\App\Http\Controllers\PartnerInventoryController::class, 'bulkAction'])->name('inventory.bulk-action');
        Route::post('inventory/{product}/reorder-images', [\App\Http\Controllers\PartnerInventoryController::class, 'reorderImages'])->name('inventory.reorder-images');
        Route::delete('inventory/{product}/images/{image}', [\App\Http\Controllers\PartnerInventoryController::class, 'deleteImage'])->name('inventory.delete-image');
        Route::resource('inventory', \App\Http\Controllers\PartnerInventoryController::class);
        Route::get('payouts', [\App\Http\Controllers\PartnerPayoutController::class, 'index'])->name('payouts.index');
        Route::resource('orders', \App\Http\Controllers\PartnerOrderController::class)->only(['index', 'show']);
    });

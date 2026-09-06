<?php

use Illuminate\Support\Facades\Route;
use Modules\CatalogDelivery\Http\Controllers\AdminContactController;
use Modules\CatalogDelivery\Http\Controllers\CategoryController;
use Modules\CatalogDelivery\Http\Controllers\ContactController;
use Modules\CatalogDelivery\Http\Controllers\LegalController;
use Modules\CatalogDelivery\Http\Controllers\PartnerInventoryController;
use Modules\CatalogDelivery\Http\Controllers\ProductController;
use Modules\CatalogDelivery\Http\Controllers\ReviewController;
use Modules\CatalogDelivery\Http\Controllers\ViewController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [ViewController::class, 'home'])->name('home');
Route::get('/shop', [ViewController::class, 'shop'])->name('shop');
Route::get('/collection', [ViewController::class, 'collection'])->name('collection');
Route::get('/product/{id}', [ViewController::class, 'product'])->name('product.show');
Route::get('/about', [ViewController::class, 'about'])->name('about');
Route::get('/contact', [ViewController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/privacy', fn () => app(LegalController::class)->show('privacy'))->name('privacy');
Route::get('/terms', fn () => app(LegalController::class)->show('terms'))->name('terms');
Route::get('/shipping', fn () => app(LegalController::class)->show('shipping'))->name('shipping');
Route::get('/returns', fn () => app(LegalController::class)->show('returns'))->name('returns');

/*
|--------------------------------------------------------------------------
| Administrative Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::post('/products/{product}/reorder-images', [ProductController::class, 'reorderImages'])->name('products.reorder-images');
    Route::delete('/products/{product}/images/{image}', [ProductController::class, 'deleteImage'])->name('products.delete-image');
    Route::resource('products', ProductController::class)->except(['show'])->names([
        'index' => 'products.index',
        'create' => 'products.create',
        'store' => 'products.store',
        'edit' => 'products.edit',
        'update' => 'products.update',
        'destroy' => 'products.destroy',
    ]);
    Route::resource('categories', CategoryController::class)->names([
        'index' => 'categories.index',
        'create' => 'categories.create',
        'store' => 'categories.store',
        'edit' => 'categories.edit',
        'update' => 'categories.update',
        'destroy' => 'categories.destroy',
    ])->except(['show']);

    /* Community Moderation */
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{id}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{id}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    /* Contact Inquiries */
    Route::get('/contacts', [AdminContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts/{id}/reply', [AdminContactController::class, 'reply'])->name('contacts.reply');
    Route::delete('/contacts/{id}', [AdminContactController::class, 'destroy'])->name('contacts.destroy');
});

/*
|--------------------------------------------------------------------------
| Artisan Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'partner'])->prefix('partner')->as('partner.')->group(function () {
    Route::post('inventory/bulk-action', [PartnerInventoryController::class, 'bulkAction'])->name('inventory.bulk-action');
    Route::post('inventory/{product}/reorder-images', [PartnerInventoryController::class, 'reorderImages'])->name('inventory.reorder-images');
    Route::delete('inventory/{product}/images/{image}', [PartnerInventoryController::class, 'deleteImage'])->name('inventory.delete-image');
    Route::resource('inventory', PartnerInventoryController::class)->except(['show']);
});

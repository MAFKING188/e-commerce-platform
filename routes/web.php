<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AdminDashboardController,
    AdminOrderController,
    AuthController,
    CartController,
    CategoryController,
    OrderController,
    PaymentController,
    ProductController,
    UserController,
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
Route::get('/about', [ViewController::class, 'about'])->name('about');
Route::get('/contact', [ViewController::class, 'contact'])->name('contact');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', fn() => view('auth.login'))->name('login');
    Route::get('/signup', fn() => view('auth.signup'))->name('signup');
});

Route::post('/createaccount', [AuthController::class, 'register']);
Route::post('/accessaccount', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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

    /* Identity & Profile */
    Route::get('/profile', [UserController::class, 'show'])->name('profile');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');

    /* Wishlist System */
    Route::get('/wishlist', function() {
        return view('wishlist', ['wishlistItems' => collect()]); 
    })->name('wishlist.index');
});

/*
|--------------------------------------------------------------------------
| Administrative Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    /* Order Management */
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{id}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::post('/orders/{id}/complete', [AdminOrderController::class, 'complete'])->name('admin.orders.complete');

    Route::resource('products', ProductController::class);
    Route::resource('users', UserController::class);
    Route::resource('categories', CategoryController::class);
});

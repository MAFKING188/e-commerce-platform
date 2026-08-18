<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\AdminUserController;
use Modules\IdentityAccess\Http\Controllers\AuthController;
use Modules\IdentityAccess\Http\Controllers\UserController;
use Modules\IdentityAccess\Http\Controllers\WishlistController;

Route::middleware('guest')->group(function () {
    Route::get('/login', fn() => view('identityaccess::auth.login'))->name('login');
    Route::get('/signup', fn() => view('identityaccess::auth.signup'))->name('signup');
});

Route::post('/createaccount', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/accessaccount', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [UserController::class, 'show'])->name('profile');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/avatar', [UserController::class, 'updateAvatar'])->name('profile.avatar');
    Route::put('/profile/password', [UserController::class, 'updatePassword'])->name('profile.password');

    Route::get('/archive', [WishlistController::class, 'index'])->name('profile.wishlist');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/dashboard', [\Modules\IdentityAccess\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [\Modules\IdentityAccess\Http\Controllers\AdminProfileController::class, 'index'])->name('profile');
    Route::resource('users', AdminUserController::class)->except(['create', 'store', 'show'])->names([
        'index' => 'users.index',
        'edit' => 'users.edit',
        'update' => 'users.update',
        'destroy' => 'users.destroy',
    ]);
    Route::post('/users/{id}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
});
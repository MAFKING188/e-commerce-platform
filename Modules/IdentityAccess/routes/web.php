<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\AdminUserController;
use Modules\IdentityAccess\Http\Controllers\AuthController;
use Modules\IdentityAccess\Http\Controllers\PasswordResetController;
use Modules\IdentityAccess\Http\Controllers\UserController;
use Modules\IdentityAccess\Http\Controllers\WishlistController;

Route::middleware('guest')->group(function () {
    Route::get('/login', fn() => view('identityaccess::auth.login'))->name('login');
    Route::get('/signup', fn() => view('identityaccess::auth.signup'))->name('signup');
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('forgot-password');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'storeNewPassword'])->name('password.store');
});

Route::post('/createaccount', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/accessaccount', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/auth/google/redirect', [\Modules\IdentityAccess\Http\Controllers\GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [\Modules\IdentityAccess\Http\Controllers\GoogleAuthController::class, 'handleCallback'])->name('auth.google.callback');

Route::middleware(['2fa.pending'])->group(function () {
    Route::get('/2fa/challenge', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'challenge'])->name('2fa.challenge');
    Route::post('/2fa/challenge/verify', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'verify'])->middleware('throttle:2fa')->name('2fa.verify');
    Route::post('/2fa/challenge/resend', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'resend'])->middleware('throttle:2fa-resend')->name('2fa.resend');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [UserController::class, 'show'])->name('profile');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/avatar', [UserController::class, 'updateAvatar'])->name('profile.avatar');
    Route::put('/profile/password', [UserController::class, 'updatePassword'])->name('profile.password');
    Route::get('/profile/security', [UserController::class, 'security'])->name('profile.security');
    Route::get('/profile/settings', [UserController::class, 'settings'])->name('profile.settings');

    Route::post('/profile/settings/twofa/enable-email', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'enableEmail'])->middleware('throttle:2fa-enroll')->name('profile.settings.twofa.enable-email');
    Route::post('/profile/settings/twofa/confirm', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'confirm'])->middleware('throttle:2fa-enroll')->name('profile.settings.twofa.confirm');
    Route::post('/profile/settings/twofa/disable', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'disable'])->middleware('throttle:2fa-enroll')->name('profile.settings.twofa.disable');

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
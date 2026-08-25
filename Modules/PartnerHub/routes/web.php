<?php

use Illuminate\Support\Facades\Route;
use Modules\PartnerHub\Http\Controllers\PartnerController;
use Modules\PartnerHub\Http\Controllers\PartnerDashboardController;
use Modules\PartnerHub\Http\Controllers\PartnerProfileController;
use Modules\PartnerHub\Http\Controllers\VendorBankDetailController;

Route::get('/artisan-profile/{id}', [PartnerProfileController::class, 'show'])->name('partner.profile');

Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::resource('partners', PartnerController::class);
    Route::post('partners/{id}/add-product', [PartnerController::class, 'addProduct'])->name('partners.add_product');
    Route::delete('partners/{id}/remove-product/{productId}', [PartnerController::class, 'removeProduct'])->name('partners.remove_product');
});

Route::prefix('partner')->middleware(['auth', 'partner'])->name('partner.')->group(function () {
    Route::get('/dashboard', [PartnerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [PartnerProfileController::class, 'profile'])->name('profile.show');
    Route::get('/profile/edit', [PartnerProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [PartnerProfileController::class, 'update'])->name('profile.update');

    Route::get('/bank-details', [VendorBankDetailController::class, 'index'])->name('bank-details.index');
    Route::get('/bank-details/create', [VendorBankDetailController::class, 'create'])->name('bank-details.create');
    Route::post('/bank-details', [VendorBankDetailController::class, 'store'])->name('bank-details.store');
    Route::get('/bank-details/{bankDetail}/edit', [VendorBankDetailController::class, 'edit'])->name('bank-details.edit');
    Route::put('/bank-details/{bankDetail}', [VendorBankDetailController::class, 'update'])->name('bank-details.update');
    Route::delete('/bank-details/{bankDetail}', [VendorBankDetailController::class, 'destroy'])->name('bank-details.destroy');
});
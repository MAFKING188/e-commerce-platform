<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Email templates CRUD
    Route::get('email-templates', [\Modules\EmailCenter\Http\Controllers\EmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::get('email-templates/create', [\Modules\EmailCenter\Http\Controllers\EmailTemplateController::class, 'create'])->name('email-templates.create');
    Route::post('email-templates', [\Modules\EmailCenter\Http\Controllers\EmailTemplateController::class, 'store'])->name('email-templates.store');
    Route::get('email-templates/{id}/edit', [\Modules\EmailCenter\Http\Controllers\EmailTemplateController::class, 'edit'])->name('email-templates.edit');
    Route::put('email-templates/{id}', [\Modules\EmailCenter\Http\Controllers\EmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::delete('email-templates/{id}', [\Modules\EmailCenter\Http\Controllers\EmailTemplateController::class, 'destroy'])->name('email-templates.destroy');

    // Compose & send
    Route::get('email-compose', [\Modules\EmailCenter\Http\Controllers\EmailSendController::class, 'compose'])->name('email.compose');
    Route::post('email-send', [\Modules\EmailCenter\Http\Controllers\EmailSendController::class, 'send'])->middleware('throttle:email')->name('email.send');
    Route::get('users/search', [\Modules\EmailCenter\Http\Controllers\EmailSendController::class, 'searchUsers'])->name('users.search');

    // Send history
    Route::get('email-logs', [\Modules\EmailCenter\Http\Controllers\EmailLogController::class, 'index'])->name('email.logs');
});

Route::prefix('partner')->middleware(['auth', 'partner'])->name('partner.')->group(function () {
    Route::get('email-compose', [\Modules\EmailCenter\Http\Controllers\PartnerEmailSendController::class, 'compose'])->name('email.compose');
    Route::post('email-send', [\Modules\EmailCenter\Http\Controllers\PartnerEmailSendController::class, 'send'])->middleware('throttle:email')->name('email.send');
    Route::get('email-logs', [\Modules\EmailCenter\Http\Controllers\PartnerEmailLogController::class, 'index'])->name('email.logs');
});
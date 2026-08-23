<?php

use Illuminate\Support\Facades\Route;
use Modules\TelemetryPipeline\Http\Controllers\AuditLogController;
use Modules\TelemetryPipeline\Http\Controllers\OutboundMailController;

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('outbound-mail', [OutboundMailController::class, 'index'])->name('outbound-mail.index');
});

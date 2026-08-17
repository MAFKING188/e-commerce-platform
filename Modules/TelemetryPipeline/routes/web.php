<?php

use Illuminate\Support\Facades\Route;
use Modules\TelemetryPipeline\Http\Controllers\TelemetryPipelineController;

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('telemetrypipelines', TelemetryPipelineController::class)->names('telemetrypipeline');
});

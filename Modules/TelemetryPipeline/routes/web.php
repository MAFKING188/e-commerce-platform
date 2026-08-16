<?php

use Illuminate\Support\Facades\Route;
use Modules\TelemetryPipeline\Http\Controllers\TelemetryPipelineController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('telemetrypipelines', TelemetryPipelineController::class)->names('telemetrypipeline');
});

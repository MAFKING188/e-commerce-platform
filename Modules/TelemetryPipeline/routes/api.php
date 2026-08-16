<?php

use Illuminate\Support\Facades\Route;
use Modules\TelemetryPipeline\Http\Controllers\TelemetryPipelineController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('telemetrypipelines', TelemetryPipelineController::class)->names('telemetrypipeline');
});

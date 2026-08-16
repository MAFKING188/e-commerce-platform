<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplacePipeline\Http\Controllers\MarketplacePipelineController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('marketplacepipelines', MarketplacePipelineController::class)->names('marketplacepipeline');
});

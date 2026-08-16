<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplacePipeline\Http\Controllers\MarketplacePipelineController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('marketplacepipelines', MarketplacePipelineController::class)->names('marketplacepipeline');
});

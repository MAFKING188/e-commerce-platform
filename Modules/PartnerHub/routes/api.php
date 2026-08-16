<?php

use Illuminate\Support\Facades\Route;
use Modules\PartnerHub\Http\Controllers\PartnerHubController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('partnerhubs', PartnerHubController::class)->names('partnerhub');
});

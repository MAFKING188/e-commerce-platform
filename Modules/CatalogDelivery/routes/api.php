<?php

use Illuminate\Support\Facades\Route;
use Modules\CatalogDelivery\Http\Controllers\CatalogDeliveryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('catalogdeliveries', CatalogDeliveryController::class)->names('catalogdelivery');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\CatalogDelivery\Http\Controllers\CatalogDeliveryController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('catalogdeliveries', CatalogDeliveryController::class)->names('catalogdelivery');
});

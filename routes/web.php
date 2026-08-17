<?php

use Illuminate\Support\Facades\Route;
use Modules\CatalogDelivery\Http\Controllers\ReviewController;

/*
|--------------------------------------------------------------------------
| Root routes (everything else lives in module route files):
|   Modules/IdentityAccess/routes/web.php
|   Modules/CatalogDelivery/routes/web.php
|   Modules/PartnerHub/routes/web.php
|   Modules/MarketplacePipeline/routes/web.php
|   Modules/TelemetryPipeline/routes/web.php
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    /* Community Reviews — member submission (admin moderation lives in CatalogDelivery) */
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});
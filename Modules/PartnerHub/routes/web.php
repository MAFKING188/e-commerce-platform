<?php

use Illuminate\Support\Facades\Route;
use Modules\PartnerHub\Http\Controllers\PartnerHubController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('partnerhubs', PartnerHubController::class)->names('partnerhub');
});

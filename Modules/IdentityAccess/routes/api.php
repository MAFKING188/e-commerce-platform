<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\AuthController;

// Public auth endpoints. Registration is intentionally WEB-ONLY: self-serve API
// account creation bypassed email verification + status gates (sweep finding 4.2).
// Reintroduce only together with an API verification flow.
Route::post('/login', [AuthController::class, 'apiLogin'])->middleware('throttle:auth');
Route::get('/user', function (Illuminate\Http\Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
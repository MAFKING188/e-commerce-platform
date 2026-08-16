<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'apiLogin']);
Route::post('/register', [AuthController::class, 'apiRegister']);
Route::get('/user', function (Illuminate\Http\Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
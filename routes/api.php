<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API Routes v1
Route::prefix('v1')->group(function () {
    // Auth routes (public)
    Route::post('/auth/login-admin', [AuthController::class, 'loginAdmin']);
    Route::post('/auth/login-peserta', [AuthController::class, 'loginPeserta']);

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });
});
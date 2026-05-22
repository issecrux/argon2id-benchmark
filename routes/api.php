<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BenchmarkController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', [BenchmarkController::class, 'health']);

// Auth routes (rate limited)
Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});
Route::prefix('auth')->group(function () {
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});

// Benchmark routes (macro layer)
Route::prefix('benchmark')->group(function () {
    Route::post('/full-auth', [BenchmarkController::class, 'fullAuth']);
    Route::post('/hash', [BenchmarkController::class, 'hashWithParams']);
});

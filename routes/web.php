<?php

use App\Http\Controllers\LoginBenchController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login-bench', LoginBenchController::class)
    ->withoutMiddleware([VerifyCsrfToken::class, ThrottleRequests::class]);

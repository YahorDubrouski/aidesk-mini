<?php

use App\Http\Controllers\Api\ApiKey\ApiKeysController;
use App\Http\Controllers\Api\Article\ArticlesController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Health\HealthController;
use App\Http\Controllers\Api\Ticket\TicketsController;
use Illuminate\Support\Facades\Route;

Route::prefix('health')->group(function () {
    Route::get('/live', [HealthController::class, 'live']);
    Route::get('/ready', [HealthController::class, 'ready']);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::prefix('api-keys')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ApiKeysController::class, 'index']);
    Route::post('/', [ApiKeysController::class, 'store']);
    Route::delete('/{apiKey}', [ApiKeysController::class, 'destroy']);
});

Route::prefix('tickets')->group(function () {
    Route::post('/', [TicketsController::class, 'store']);
    Route::get('/{ticket}', [TicketsController::class, 'show']);
});

Route::prefix('articles')->group(function () {
    Route::post('/search', [ArticlesController::class, 'search']);
});

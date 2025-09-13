<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\TicketsController;
use Illuminate\Support\Facades\Route;

Route::prefix('health')->group(function () {
    Route::get('/live', [HealthController::class, 'live']);
    Route::get('/ready', [HealthController::class, 'ready']);
});

Route::prefix('tickets')->group(function () {
    Route::post('/', [TicketsController::class, 'store']);
    Route::get('/{ticket}', [TicketsController::class, 'show']);
});

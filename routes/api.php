<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'ok' => true,
        'ts' => now()->toIso8601String(),
        'service' => config('app.name'),
    ]);
});

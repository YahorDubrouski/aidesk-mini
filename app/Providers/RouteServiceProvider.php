<?php

namespace App\Providers;

use App\Models\Ticket;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::bind('ticket', function (string $publicId) {
            return Ticket::query()->where('public_id', $publicId)->firstOrFail();
        });
    }
}

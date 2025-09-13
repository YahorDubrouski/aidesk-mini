<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Ticket;
use App\Observers\TicketObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        parent::boot();

        Ticket::observe(TicketObserver::class);
    }
}

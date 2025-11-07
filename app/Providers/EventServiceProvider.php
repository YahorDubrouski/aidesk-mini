<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Ticket\TicketAnalyzed;
use App\Listeners\Ticket\SaveTicketAnalysisListener;
use App\Models\Ticket;
use App\Observers\TicketObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TicketAnalyzed::class => [
            SaveTicketAnalysisListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        Ticket::observe(TicketObserver::class);
    }
}

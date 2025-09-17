<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\Ticket\AnalyzeTicketJob;
use App\Models\Ticket;
use Illuminate\Support\Str;

final class TicketObserver
{
    public function creating(Ticket $ticket): void
    {
        if (empty($ticket->public_id)) {
            $ticket->public_id = (string) Str::ulid();
        }
    }

    public function created(Ticket $ticket): void
    {
        AnalyzeTicketJob::dispatch($ticket->id);
    }
}

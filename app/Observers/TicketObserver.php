<?php

declare(strict_types=1);

namespace App\Observers;

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
}

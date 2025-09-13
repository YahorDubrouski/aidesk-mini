<?php

declare(strict_types=1);

namespace App\Enums\Ticket;

enum TicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case Closed = 'closed';
}

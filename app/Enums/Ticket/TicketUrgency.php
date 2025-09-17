<?php

declare(strict_types=1);

namespace App\Enums\Ticket;

enum TicketUrgency: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';
}

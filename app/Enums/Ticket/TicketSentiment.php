<?php

declare(strict_types=1);

namespace App\Enums\Ticket;

enum TicketSentiment: string
{
    case Negative = 'negative';
    case Neutral = 'neutral';
    case Positive = 'positive';
}

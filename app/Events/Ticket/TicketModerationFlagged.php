<?php

declare(strict_types=1);

namespace App\Events\Ticket;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketModerationFlagged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $ticketId,
        /** @var array{flagged:bool, category?:mixed, reason?:?string} */
        public array $moderation
    ) {}
}

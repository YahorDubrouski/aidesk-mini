<?php

declare(strict_types=1);

namespace App\Events\Ticket;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketAnalyzed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $ticketId,
        /** @var array{provider:string, model:string, result:array, usage:array} */
        public array $analysis
    ) {
    }
}

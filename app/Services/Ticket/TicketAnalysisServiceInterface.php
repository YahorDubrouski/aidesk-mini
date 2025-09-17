<?php

declare(strict_types=1);

namespace App\Services\Ticket;

use Illuminate\Http\Client\ConnectionException;

interface TicketAnalysisServiceInterface
{
    /**
     * Analyze a ticket: moderation → AI analysis → emit events.
     *
     * @throws ConnectionException
     */
    public function analyze(int $ticketId): void;
}

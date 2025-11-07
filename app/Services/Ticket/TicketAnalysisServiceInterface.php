<?php

declare(strict_types=1);

namespace App\Services\Ticket;

use App\DTOs\Ai\TextAnalysisResult;
use Illuminate\Http\Client\ConnectionException;

interface TicketAnalysisServiceInterface
{
    /**
     * Analyze a ticket: moderation → AI analysis → emit events.
     *
     * @throws ConnectionException
     */
    public function analyze(int $ticketId): void;

    /**
     * Save AI analysis results and update ticket with triage fields.
     */
    public function saveAnalysisAndUpdateTicket(int $ticketId, TextAnalysisResult $analysis): void;
}

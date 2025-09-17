<?php

declare(strict_types=1);

namespace App\Services\Ticket;

use App\Events\Ticket\TicketAnalyzed;
use App\Events\Ticket\TicketModerationFailed;
use App\Models\Ticket;
use App\Services\Ai\AiClientInterface;

final class TicketAnalysisService implements TicketAnalysisServiceInterface
{
    public function __construct(private readonly AiClientInterface $aiClient)
    {
    }

    public function analyze(int $ticketId): void
    {
        $ticket = Ticket::query()->find($ticketId);
        if (!$ticket) {
            return;
        }

        $textForModeration = trim(
            ($ticket->subject ? "Subject: {$ticket->subject}\n" : '')
            . "Body: {$ticket->body}"
        );

        $moderation = $this->aiClient->moderate($textForModeration);
        if (!empty($moderation['flagged'])) {
            event(new TicketModerationFailed($ticket->id, $moderation));
            return;
        }

        $analysis = $this->aiClient->analyzeTicket($ticket->body, $ticket->subject);
        event(new TicketAnalyzed($ticket->id, $analysis));
    }
}

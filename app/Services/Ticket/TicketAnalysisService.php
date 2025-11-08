<?php

declare(strict_types=1);

namespace App\Services\Ticket;

use App\DTOs\Ai\TextAnalysisResult;
use App\Enums\Ticket\TicketSentiment;
use App\Enums\Ticket\TicketUrgency;
use App\Events\Ticket\TicketAnalyzed;
use App\Events\Ticket\TicketModerationFlagged;
use App\Models\AiAnalysis;
use App\Models\Ticket;
use App\Services\Ai\AiClientInterface;

final readonly class TicketAnalysisService implements TicketAnalysisServiceInterface
{
    public function __construct(private AiClientInterface $aiClient) {}

    public function analyze(int $ticketId): void
    {
        $ticket = Ticket::query()->findOrFail($ticketId);

        if (config('features.ticket_ai_moderation')) {
            $textToModerate = trim(
                ($ticket->subject ? "Subject: {$ticket->subject}\n" : '')
                ."Body: {$ticket->body}"
            );

            $moderation = $this->aiClient->moderate($textToModerate);
            if ($moderation->flagged) {
                event(new TicketModerationFlagged($ticket->id, $moderation->toArray()));

                return;
            }
        }

        if (! config('features.ticket_ai_analysis')) {
            return;
        }

        $analysis = $this->aiClient->analyzeText($ticket->body, $ticket->subject);
        event(new TicketAnalyzed($ticket->id, $analysis));
    }

    public function saveAnalysisAndUpdateTicket(int $ticketId, TextAnalysisResult $analysis): void
    {
        $ticket = Ticket::query()->find($ticketId);
        if (! $ticket) {
            return;
        }

        $result = $analysis->result;
        $usage = $analysis->usage;

        AiAnalysis::query()->create([
            'ticket_id' => $ticketId,
            'provider' => $analysis->provider,
            'model' => $analysis->model,
            'schema_version' => 1,
            'usage_prompt_tokens' => $usage->promptTokens,
            'usage_completion_tokens' => $usage->completionTokens,
            'usage_total_tokens' => $usage->totalTokens,
            'cost_usd' => $usage->costUsd,
            'result' => $result->toArray(),
        ]);

        $ticket->update([
            'category' => $result->category,
            'urgency' => TicketUrgency::from($result->urgency),
            'sentiment' => $result->sentiment ? TicketSentiment::from($result->sentiment) : null,
            'summary' => $result->summary,
        ]);
    }
}

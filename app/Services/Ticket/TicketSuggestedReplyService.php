<?php

declare(strict_types=1);

namespace App\Services\Ticket;

use App\DTOs\Article\ArticleSimilarityMatch;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyPassage;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplySource;
use App\Exceptions\SuggestedReplyFeatureDisabledException;
use App\Models\Ticket;
use App\Models\TicketSuggestedReply;
use App\Services\Embedding\ArticleEmbeddingService;
use App\Services\Ticket\SuggestedReply\SuggestedReplyGeneratorInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Orchestrates RAG suggested replies: retrieve top-k articles, then ground generation on them.
 */
final readonly class TicketSuggestedReplyService
{
    public const DEFAULT_PASSAGE_LIMIT = 5;

    public const SCHEMA_VERSION = 1;

    public function __construct(
        private ArticleEmbeddingService $articleEmbeddingService,
        private SuggestedReplyGeneratorInterface $suggestedReplyGenerator,
    ) {}

    /**
     * @throws SuggestedReplyFeatureDisabledException
     * @throws ModelNotFoundException
     */
    public function suggestForTicket(int $ticketId, int $limit = self::DEFAULT_PASSAGE_LIMIT): SuggestedReplyResult
    {
        if (! config('features.ticket_ai_suggested_reply')) {
            throw new SuggestedReplyFeatureDisabledException;
        }

        $ticket = Ticket::query()->findOrFail($ticketId);
        $question = $this->buildQuestion($ticket);
        $matches = $this->articleEmbeddingService->searchWithScores($question, $limit);
        $passages = array_map(
            static fn (ArticleSimilarityMatch $match): SuggestedReplyPassage => new SuggestedReplyPassage(
                id: $match->article->id,
                title: $match->article->title,
                body: $match->article->body,
                similarity: $match->similarity,
            ),
            $matches,
        );

        $result = $this->suggestedReplyGenerator->generate($question, $passages);
        $this->persist($ticket->id, $result);

        return $result;
    }

    private function persist(int $ticketId, SuggestedReplyResult $result): void
    {
        TicketSuggestedReply::query()->create([
            'ticket_id' => $ticketId,
            'provider' => $result->provider,
            'model' => $result->model,
            'schema_version' => self::SCHEMA_VERSION,
            'answer' => $result->answer,
            'refused' => $result->refused,
            'refuse_reason' => $result->refuseReason,
            'sources' => array_map(
                static fn (SuggestedReplySource $source): array => $source->toArray(),
                $result->sources,
            ),
            'usage_prompt_tokens' => $result->usage->promptTokens,
            'usage_completion_tokens' => $result->usage->completionTokens,
            'usage_total_tokens' => $result->usage->totalTokens,
            'cost_usd' => $result->usage->costUsd,
        ]);
    }

    private function buildQuestion(Ticket $ticket): string
    {
        return trim(
            ($ticket->subject ? "Subject: {$ticket->subject}\n" : '')
            ."Body: {$ticket->body}"
        );
    }
}

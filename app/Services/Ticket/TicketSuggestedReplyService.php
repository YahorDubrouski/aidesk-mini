<?php

declare(strict_types=1);

namespace App\Services\Ticket;

use App\DTOs\Article\ArticleSimilarityMatch;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyPassage;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\Exceptions\SuggestedReplyFeatureDisabledException;
use App\Models\Ticket;
use App\Services\Embedding\ArticleEmbeddingService;
use App\Services\Ticket\SuggestedReply\SuggestedReplyGeneratorInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Orchestrates RAG suggested replies: retrieve top-k articles, then ground generation on them.
 */
final readonly class TicketSuggestedReplyService
{
    public const DEFAULT_PASSAGE_LIMIT = 5;

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

        return $this->suggestedReplyGenerator->generate($question, $passages);
    }

    private function buildQuestion(Ticket $ticket): string
    {
        return trim(
            ($ticket->subject ? "Subject: {$ticket->subject}\n" : '')
            ."Body: {$ticket->body}"
        );
    }
}

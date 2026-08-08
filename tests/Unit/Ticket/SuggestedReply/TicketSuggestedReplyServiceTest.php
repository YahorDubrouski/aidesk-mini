<?php

declare(strict_types=1);

namespace Tests\Unit\Ticket\SuggestedReply;

use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\Exceptions\SuggestedReplyFeatureDisabledException;
use App\Models\AiAnalysis;
use App\Models\Article;
use App\Models\Ticket;
use App\Models\TicketSuggestedReply;
use App\Services\Embedding\ArticleEmbeddingService;
use App\Services\Ticket\TicketSuggestedReplyService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ticket suggested-reply orchestration:
 * - Feature flag must be on.
 * - Missing tickets fail loudly.
 * - Only vector-search hits are passed into grounded generation.
 * - Each run is persisted without touching triage AiAnalysis rows.
 */
final class TicketSuggestedReplyServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Given
     * - The suggested-reply feature flag is disabled.
     * When
     * - A suggested reply is requested for a ticket.
     * Then
     * - A feature-disabled error is thrown and nothing is persisted.
     */
    public function test_when_feature_flag_is_off_then_service_throws(): void
    {
        // Arrange
        config(['features.ticket_ai_suggested_reply' => false]);
        $ticket = Ticket::factory()->create();
        $service = $this->app->make(TicketSuggestedReplyService::class);

        // Assert
        $this->expectException(SuggestedReplyFeatureDisabledException::class);

        try {
            // Act
            $service->suggestForTicket($ticket->id);
        } finally {
            $this->assertSame(0, TicketSuggestedReply::query()->count());
        }
    }

    /**
     * Given
     * - The ticket id does not exist.
     * When
     * - A suggested reply is requested.
     * Then
     * - A model-not-found error is thrown.
     */
    public function test_when_ticket_is_missing_then_service_throws(): void
    {
        // Arrange
        config(['features.ticket_ai_suggested_reply' => true]);
        $service = $this->app->make(TicketSuggestedReplyService::class);

        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $service->suggestForTicket(999999);
    }

    /**
     * Given
     * - A published article about password reset has an embedding.
     * - A ticket asks how to reset a password.
     * When
     * - A suggested reply is generated.
     * Then
     * - The result cites that article and a suggested-reply row is stored.
     */
    public function test_when_search_returns_articles_then_sources_match_and_reply_is_persisted(): void
    {
        // Arrange
        config([
            'features.ticket_ai_suggested_reply' => true,
            'features.article_ai_embeddings' => true,
        ]);
        $article = Article::factory()->create([
            'title' => 'Password reset',
            'body' => 'Open Settings → Security → Reset password. Link expires in 24 hours.',
            'is_published' => true,
        ]);
        $this->app->make(ArticleEmbeddingService::class)->generateForArticle($article);

        $ticket = Ticket::factory()->create([
            'subject' => 'Password help',
            'body' => 'How do I reset my password?',
        ]);
        $analysisCountBefore = AiAnalysis::query()->count();
        $service = $this->app->make(TicketSuggestedReplyService::class);

        // Act
        $result = $service->suggestForTicket($ticket->id);

        // Assert
        $this->assertFalse($result->refused);
        $this->assertNotEmpty($result->sources);
        $this->assertSame($article->id, $result->sources[0]->id);
        $this->assertNotNull($result->sources[0]->similarity);
        $this->assertGreaterThan(0.0, $result->sources[0]->similarity);
        $this->assertStringContainsString('Password reset', $result->answer);

        $stored = TicketSuggestedReply::query()->where('ticket_id', $ticket->id)->sole();
        $this->assertFalse($stored->refused);
        $this->assertNull($stored->refuse_reason);
        $this->assertSame($result->answer, $stored->answer);
        $this->assertSame($article->id, $stored->sources[0]['id']);
        $this->assertSame(
            $analysisCountBefore,
            AiAnalysis::query()->count(),
            'Suggested reply must not create triage AiAnalysis rows',
        );
    }

    /**
     * Given
     * - No knowledge articles exist.
     * When
     * - A suggested reply is generated for a ticket.
     * Then
     * - The refused result is persisted with empty sources.
     */
    public function test_when_search_returns_nothing_then_refused_reply_is_persisted(): void
    {
        // Arrange
        config([
            'features.ticket_ai_suggested_reply' => true,
            'features.article_ai_embeddings' => true,
        ]);
        $ticket = Ticket::factory()->create([
            'subject' => 'Password help',
            'body' => 'How do I reset my password?',
        ]);
        $analysisCountBefore = AiAnalysis::query()->count();
        $service = $this->app->make(TicketSuggestedReplyService::class);

        // Act
        $result = $service->suggestForTicket($ticket->id);

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_EMPTY_PASSAGES, $result->refuseReason);
        $this->assertSame([], $result->sources);

        $stored = TicketSuggestedReply::query()->where('ticket_id', $ticket->id)->sole();
        $this->assertTrue($stored->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_EMPTY_PASSAGES, $stored->refuse_reason);
        $this->assertSame([], $stored->sources);
        $this->assertSame(
            $analysisCountBefore,
            AiAnalysis::query()->count(),
            'Suggested reply must not create triage AiAnalysis rows',
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Ticket\SuggestedReply;

use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\Exceptions\SuggestedReplyFeatureDisabledException;
use App\Models\Article;
use App\Models\Ticket;
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
     * - A feature-disabled error is thrown.
     */
    public function test_when_feature_flag_is_off_then_service_throws(): void
    {
        // Arrange
        config(['features.ticket_ai_suggested_reply' => false]);
        $ticket = Ticket::factory()->create();
        $service = $this->app->make(TicketSuggestedReplyService::class);

        // Assert
        $this->expectException(SuggestedReplyFeatureDisabledException::class);

        // Act
        $service->suggestForTicket($ticket->id);
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
     * - The result cites that article and includes a similarity score.
     */
    public function test_when_search_returns_articles_then_sources_match_retrieved_passages(): void
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
    }

    /**
     * Given
     * - No knowledge articles exist.
     * When
     * - A suggested reply is generated for a ticket.
     * Then
     * - The result is refused for empty passages.
     */
    public function test_when_search_returns_nothing_then_reply_is_refused(): void
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
        $service = $this->app->make(TicketSuggestedReplyService::class);

        // Act
        $result = $service->suggestForTicket($ticket->id);

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_EMPTY_PASSAGES, $result->refuseReason);
        $this->assertSame([], $result->sources);
    }
}

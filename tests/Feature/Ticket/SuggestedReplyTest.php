<?php

declare(strict_types=1);

namespace Tests\Feature\Ticket;

use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\Models\Article;
use App\Models\Ticket;
use App\Models\TicketSuggestedReply;
use App\Services\Embedding\ArticleEmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suggested-reply API:
 * - Happy path returns grounded answer + sources.
 * - Empty knowledge base refuses clearly.
 * - Missing ticket is 404; feature flag off is 403.
 */
final class SuggestedReplyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Given
     * - A published password article with an embedding and a matching ticket.
     * When
     * - POST /api/tickets/{public_id}/suggested-reply is called.
     * Then
     * - The response includes an answer and that article as a source.
     */
    public function test_when_ticket_matches_knowledge_then_suggested_reply_returns_sources(): void
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

        // Act
        $response = $this->postJson("/api/tickets/{$ticket->public_id}/suggested-reply");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.refused', false)
            ->assertJsonPath('data.sources.0.id', $article->id)
            ->assertJsonStructure([
                'data' => [
                    'answer',
                    'refused',
                    'refuse_reason',
                    'sources' => [
                        ['id', 'title', 'similarity'],
                    ],
                    'provider',
                    'model',
                    'usage' => [
                        'prompt_tokens',
                        'completion_tokens',
                        'total_tokens',
                        'cost_usd',
                    ],
                ],
            ]);
        $this->assertStringContainsString('Password reset', (string) $response->json('data.answer'));
        $this->assertSame(1, TicketSuggestedReply::query()->where('ticket_id', $ticket->id)->count());
    }

    /**
     * Given
     * - A ticket exists but the knowledge base has no articles.
     * When
     * - Suggested reply is requested.
     * Then
     * - The API returns a refused grounded reply.
     */
    public function test_when_knowledge_base_is_empty_then_suggested_reply_is_refused(): void
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

        // Act
        $response = $this->postJson("/api/tickets/{$ticket->public_id}/suggested-reply");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.refused', true)
            ->assertJsonPath('data.refuse_reason', SuggestedReplyResult::REFUSE_REASON_EMPTY_PASSAGES)
            ->assertJsonPath('data.sources', [])
            ->assertJsonPath('data.answer', SuggestedReplyResult::DEFAULT_REFUSE_ANSWER);
    }

    /**
     * Given
     * - The ticket id does not exist.
     * When
     * - Suggested reply is requested.
     * Then
     * - The API responds with 404.
     */
    public function test_when_ticket_is_missing_then_suggested_reply_returns_not_found(): void
    {
        // Arrange
        config(['features.ticket_ai_suggested_reply' => true]);

        // Act
        $response = $this->postJson('/api/tickets/01MISSINGTICKET000000000000/suggested-reply');

        // Assert
        $response->assertNotFound();
    }

    /**
     * Given
     * - The suggested-reply feature flag is disabled.
     * When
     * - Suggested reply is requested for an existing ticket.
     * Then
     * - The API responds with 403 and nothing is persisted.
     */
    public function test_when_feature_flag_is_off_then_suggested_reply_is_forbidden(): void
    {
        // Arrange
        config(['features.ticket_ai_suggested_reply' => false]);
        $ticket = Ticket::factory()->create();

        // Act
        $response = $this->postJson("/api/tickets/{$ticket->public_id}/suggested-reply");

        // Assert
        $response->assertForbidden();
        $this->assertSame(0, TicketSuggestedReply::query()->count());
    }

    /**
     * Given
     * - An invalid retrieval limit is sent.
     * When
     * - Suggested reply is requested.
     * Then
     * - The API responds with a validation error.
     */
    public function test_when_limit_is_invalid_then_suggested_reply_is_validated(): void
    {
        // Arrange
        config(['features.ticket_ai_suggested_reply' => true]);
        $ticket = Ticket::factory()->create();

        // Act
        $response = $this->postJson("/api/tickets/{$ticket->public_id}/suggested-reply", [
            'limit' => 99,
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }
}

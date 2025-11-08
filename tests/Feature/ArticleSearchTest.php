<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Locale\Language;
use App\Models\Article;
use App\Services\Embedding\ArticleEmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ArticleSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_articles(): void
    {
        $article = Article::factory()->create([
            'title' => 'How to Reset Your Password',
            'body' => 'If you forgot your password, click the Forgot Password link.',
            'language' => Language::English,
            'is_published' => true,
        ]);

        $articleEmbeddingService = app(ArticleEmbeddingService::class);
        $articleEmbeddingService->generateForArticle($article);

        $response = $this->postJson('/api/articles/search', [
            'query' => 'password reset',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'public_id',
                        'title',
                        'slug',
                        'summary',
                        'language',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_search_validates_query(): void
    {
        $response = $this->postJson('/api/articles/search', [
            'query' => 'ab',
        ]);

        $response->assertStatus(422);
    }

    public function test_search_returns_empty_when_no_embeddings(): void
    {
        Article::factory()->create([
            'title' => 'Test Article',
            'body' => 'Test body',
            'is_published' => false,
        ]);

        $response = $this->postJson('/api/articles/search', [
            'query' => 'test query',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_search_finds_relevant_article_by_meaning(): void
    {
        $articleEmbeddingService = app(ArticleEmbeddingService::class);

        $passwordArticle = Article::factory()->create([
            'title' => 'How to Reset Your Password',
            'body' => 'If you forgot your password, click the "Forgot Password" link on the login page. Enter your email address and we will send you a reset link.',
            'language' => Language::English,
            'is_published' => true,
        ]);

        $apiArticle = Article::factory()->create([
            'title' => 'Understanding API Rate Limits',
            'body' => 'API rate limits prevent abuse and ensure fair usage. Each API key has a daily quota. You can check your usage in the API Keys dashboard.',
            'language' => Language::English,
            'is_published' => true,
        ]);

        $ticketArticle = Article::factory()->create([
            'title' => 'Creating Your First Ticket',
            'body' => 'To create a support ticket, navigate to the Tickets section and click "New Ticket". Fill in the subject and description, then submit. Our team will respond within 24 hours.',
            'language' => Language::English,
            'is_published' => true,
        ]);

        $articleEmbeddingService->generateForArticle($passwordArticle);
        $articleEmbeddingService->generateForArticle($apiArticle);
        $articleEmbeddingService->generateForArticle($ticketArticle);

        // Refresh articles to ensure embeddings are loaded
        $passwordArticle->refresh();
        $apiArticle->refresh();
        $ticketArticle->refresh();

        $response = $this->postJson('/api/articles/search', [
            'query' => 'I cannot access my account because I lost my login credentials',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.title', 'How to Reset Your Password');
    }
}

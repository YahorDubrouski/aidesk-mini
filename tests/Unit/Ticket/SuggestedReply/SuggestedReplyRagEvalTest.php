<?php

declare(strict_types=1);

namespace Tests\Unit\Ticket\SuggestedReply;

use App\Enums\Locale\Language;
use App\Models\Article;
use App\Models\Ticket;
use App\Services\Embedding\ArticleEmbeddingService;
use App\Services\Ticket\TicketSuggestedReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Fixture-driven RAG smoke eval against Fake AI (OPENAI_FAKE).
 * Proves grounded answer + sources for answerable tickets, and clear refuse otherwise.
 */
final class SuggestedReplyRagEvalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function ragEvalCases(): array
    {
        /** @var array{catalog: array<string, array<string, mixed>>, cases: list<array<string, mixed>>} $fixtures */
        $fixtures = require dirname(__DIR__, 3).'/Fixtures/SuggestedReply/rag_eval_cases.php';

        $dataset = [];
        foreach ($fixtures['cases'] as $case) {
            $dataset[$case['name']] = [
                array_merge($case, ['catalog' => $fixtures['catalog']]),
            ];
        }

        return $dataset;
    }

    /**
     * @param  array{
     *     name: string,
     *     articles: list<string>,
     *     ticket: array{subject: string, body: string},
     *     expect: array{
     *         refused: bool,
     *         refuse_reason?: string,
     *         source_slugs?: list<string>,
     *         answer_contains?: list<string>
     *     },
     *     catalog: array<string, array<string, mixed>>
     * }  $case
     */
    #[DataProvider('ragEvalCases')]
    public function test_rag_eval_case(array $case): void
    {
        // Arrange
        config([
            'features.ticket_ai_suggested_reply' => true,
            'features.article_ai_embeddings' => true,
        ]);

        $articlesBySlug = $this->seedKnowledgeBase($case['articles'], $case['catalog']);
        $ticket = Ticket::factory()->create($case['ticket']);
        $service = $this->app->make(TicketSuggestedReplyService::class);

        // Act
        $result = $service->suggestForTicket($ticket->id);

        // Assert
        $expect = $case['expect'];
        $this->assertSame(
            $expect['refused'],
            $result->refused,
            "Case [{$case['name']}] refused mismatch",
        );

        if ($expect['refused']) {
            $this->assertSame(
                $expect['refuse_reason'] ?? null,
                $result->refuseReason,
                "Case [{$case['name']}] refuse_reason mismatch",
            );
            $this->assertSame([], $result->sources);

            return;
        }

        $expectedSlugs = $expect['source_slugs'] ?? [];
        $this->assertNotEmpty($result->sources, "Case [{$case['name']}] expected sources");
        $this->assertSame(
            $articlesBySlug[$expectedSlugs[0]]->id,
            $result->sources[0]->id,
            "Case [{$case['name']}] first source slug mismatch",
        );

        foreach ($expect['answer_contains'] ?? [] as $needle) {
            $this->assertStringContainsString(
                $needle,
                $result->answer,
                "Case [{$case['name']}] answer missing [{$needle}]",
            );
        }
    }

    /**
     * @param  list<string>  $slugs
     * @param  array<string, array<string, mixed>>  $catalog
     * @return array<string, Article>
     */
    private function seedKnowledgeBase(array $slugs, array $catalog): array
    {
        $embeddingService = $this->app->make(ArticleEmbeddingService::class);
        $articlesBySlug = [];

        foreach ($slugs as $slug) {
            $definition = $catalog[$slug] ?? null;
            $this->assertNotNull($definition, "Unknown catalog slug [{$slug}]");

            $article = Article::factory()->create([
                'slug' => $slug,
                'title' => $definition['title'],
                'body' => $definition['body'],
                'is_published' => $definition['is_published'],
                'language' => Language::English,
            ]);
            $embeddingService->generateForArticle($article);
            $articlesBySlug[$slug] = $article->fresh();
        }

        return $articlesBySlug;
    }
}

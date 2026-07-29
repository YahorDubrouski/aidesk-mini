<?php

declare(strict_types=1);

namespace App\Services\Embedding;

use App\DTOs\Article\ArticleSimilarityMatch;
use App\Models\Article;
use App\Utils\VectorMath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

final readonly class ArticleEmbeddingService
{
    public const EMBEDDING_VERSION = 1;

    public function __construct(
        private EmbeddingService $embeddingService
    ) {}

    public function generateForArticle(Article $article): void
    {
        if (! config('features.article_ai_embeddings')) {
            return;
        }

        $newChecksum = $this->calculateChecksum($article);

        if ($article->checksum_sha256 === $newChecksum && $article->embedding_vector !== null) {
            return;
        }

        $text = $this->buildTextForEmbedding($article);
        $vector = $this->embeddingService->generate($text);

        $this->storeEmbedding($article, $vector, $newChecksum);
    }

    /**
     * Search articles using semantic similarity.
     *
     * Converts the search query to an embedding vector, then compares it
     * with all article embeddings to find the most semantically similar articles.
     *
     * @param  string  $query  Search query text
     * @param  int  $limit  Maximum number of results to return
     * @return Collection Articles sorted by similarity (most similar first)
     */
    public function search(string $query, int $limit = 10): Collection
    {
        $matches = $this->searchWithScores($query, $limit);
        $articleModels = array_map(
            static fn (ArticleSimilarityMatch $match): Article => $match->article,
            $matches,
        );

        return new Collection($articleModels);
    }

    /**
     * Same ranking as search(), but keeps cosine similarity with each article.
     * Example: top hit might be article #12 with similarity 0.87.
     *
     * @return list<ArticleSimilarityMatch>
     */
    public function searchWithScores(string $query, int $limit = 10): array
    {
        if (! config('features.article_ai_embeddings')) {
            return [];
        }

        // Step 1: Convert search query to numbers (embedding)
        $queryVector = $this->embeddingService->generate($query);

        // Step 2: Get all published articles that have embeddings
        $articles = Article::query()
            ->where('is_published', true)
            ->whereNotNull('embedded_at')
            ->get();

        // Step 3: Compare query numbers with each article's numbers
        // Calculate how similar they are (0.0 = different, 1.0 = identical)
        /** @var SupportCollection<int, ArticleSimilarityMatch> $ranked */
        $ranked = $articles
            ->map(fn (Article $article) => $this->calculateSimilarity($article, $queryVector))
            ->filter() // Remove articles without embeddings
            ->sortByDesc(static fn (ArticleSimilarityMatch $match): float => $match->similarity) // Best matches first (highest similarity score)
            ->take($limit) // Get top N results
            ->values();

        return $ranked->all();
    }

    private function buildTextForEmbedding(Article $article): string
    {
        return trim($article->title."\n".$article->body);
    }

    private function calculateChecksum(Article $article): string
    {
        return hash('sha256', $article->title.$article->body);
    }

    private function storeEmbedding(Article $article, array $vector, string $checksum): void
    {
        $article->update([
            'embedding_version' => self::EMBEDDING_VERSION,
            'embedded_at' => now(),
            'checksum_sha256' => $checksum,
            'embedding_vector' => json_encode($vector),
        ]);
    }

    private function calculateSimilarity(Article $article, array $queryVector): ?ArticleSimilarityMatch
    {
        $articleVector = $this->getEmbedding($article);
        if (! $articleVector) {
            return null;
        }

        return new ArticleSimilarityMatch(
            article: $article,
            similarity: VectorMath::cosineSimilarity($queryVector, $articleVector),
        );
    }

    private function getEmbedding(Article $article): ?array
    {
        if (! $article->embedding_vector) {
            return null;
        }

        $decoded = json_decode($article->embedding_vector, true);

        return is_array($decoded) ? $decoded : null;
    }
}

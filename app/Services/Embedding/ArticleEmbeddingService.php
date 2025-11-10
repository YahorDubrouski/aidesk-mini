<?php

declare(strict_types=1);

namespace App\Services\Embedding;

use App\Models\Article;
use App\Utils\VectorMath;
use Illuminate\Database\Eloquent\Collection;

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
     * @param string $query Search query text
     * @param int $limit Maximum number of results to return
     * @return Collection Articles sorted by similarity (most similar first)
     */
    public function search(string $query, int $limit = 10): Collection
    {
        if (! config('features.article_ai_embeddings')) {
            return new Collection;
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
        $results = $articles->map(fn (Article $article) => $this->calculateSimilarity($article, $queryVector))
            ->filter() // Remove articles without embeddings
            ->sortByDesc('similarity') // Best matches first (highest similarity score)
            ->take($limit) // Get top N results
            ->values();

        // Step 4: Return articles in order of similarity
        // Preserve order by mapping results directly instead of using whereIn
        $articleModels = $results->map(fn ($result) => $result['article'])->all();

        return new Collection($articleModels);
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

    private function calculateSimilarity(Article $article, array $queryVector): ?array
    {
        $articleVector = $this->getEmbedding($article);
        if (! $articleVector) {
            return null;
        }

        $similarity = VectorMath::cosineSimilarity($queryVector, $articleVector);

        return [
            'article' => $article,
            'similarity' => $similarity,
        ];
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

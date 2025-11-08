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
    ) {
    }

    public function generateForArticle(Article $article): void
    {
        $newChecksum = $this->calculateChecksum($article);

        if ($article->checksum_sha256 === $newChecksum && $article->embedding_vector !== null) {
            return;
        }

        $text = $this->buildTextForEmbedding($article);
        $vector = $this->embeddingService->generate($text);

        $this->storeEmbedding($article, $vector, $newChecksum);
    }

    public function search(string $query, int $limit = 10): Collection
    {
        $queryVector = $this->embeddingService->generate($query);

        $articles = Article::query()
            ->where('is_published', true)
            ->whereNotNull('embedded_at')
            ->get();

        $results = $articles->map(fn (Article $article) => $this->calculateSimilarity($article, $queryVector))
            ->filter()
            ->sortByDesc('similarity')
            ->take($limit)
            ->values();

        $articleIds = $results->map(fn ($result) => $result['article']->id)->all();

        return Article::query()->whereIn('id', $articleIds)->get();
    }

    private function buildTextForEmbedding(Article $article): string
    {
        return trim($article->title . "\n" . $article->body);
    }

    private function calculateChecksum(Article $article): string
    {
        return hash('sha256', $article->title . $article->body);
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
        if (!$articleVector) {
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
        if (!$article->embedding_vector) {
            return null;
        }

        $decoded = json_decode($article->embedding_vector, true);
        return is_array($decoded) ? $decoded : null;
    }
}

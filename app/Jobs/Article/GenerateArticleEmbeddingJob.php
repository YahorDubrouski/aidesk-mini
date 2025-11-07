<?php

declare(strict_types=1);

namespace App\Jobs\Article;

use App\Models\Article;
use App\Services\Embedding\ArticleEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateArticleEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $articleId
    ) {
    }

    public function handle(ArticleEmbeddingService $articleEmbeddingService): void
    {
        $article = Article::query()->find($this->articleId);
        if (!$article) {
            return;
        }

        $articleEmbeddingService->generateForArticle($article);
    }
}

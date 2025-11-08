<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\Article\GenerateArticleEmbeddingJob;
use App\Models\Article;
use Illuminate\Support\Str;

final class ArticleObserver
{
    public function creating(Article $article): void
    {
        if (empty($article->public_id)) {
            $article->public_id = (string) Str::ulid();
        }
    }

    public function created(Article $article): void
    {
        if ($article->is_published) {
            GenerateArticleEmbeddingJob::dispatch($article->id);
        }
    }

    public function updated(Article $article): void
    {
        if (!$article->is_published) {
            return;
        }

        $wasChanged = $article->wasChanged('title') || $article->wasChanged('body');
        if ($wasChanged) {
            GenerateArticleEmbeddingJob::dispatch($article->id);
        }
    }
}

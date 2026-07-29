<?php

declare(strict_types=1);

namespace App\DTOs\Article;

use App\Models\Article;

final readonly class ArticleSimilarityMatch
{
    public function __construct(
        public Article $article,
        public float $similarity,
    ) {}
}

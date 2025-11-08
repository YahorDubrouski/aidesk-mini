<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Article\SearchArticleRequest;
use App\Http\Resources\Api\Article\ListResource;
use App\Services\Embedding\ArticleEmbeddingService;

final class ArticlesController extends Controller
{
    public function __construct(
        private readonly ArticleEmbeddingService $articleEmbeddingService
    ) {}

    public function search(SearchArticleRequest $request): ListResource
    {
        $validated = $request->validated();

        $limit = (int) ($validated['limit'] ?? 10);
        $articles = $this->articleEmbeddingService->search($validated['query'], $limit);

        return new ListResource($articles);
    }
}

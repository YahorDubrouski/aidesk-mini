<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Article\SearchArticleRequest;
use App\Http\Resources\Api\Article\ListResource;
use App\Services\Embedding\ArticleEmbeddingService;
use OpenApi\Attributes as OA;

final class ArticlesController extends Controller
{
    public function __construct(
        private readonly ArticleEmbeddingService $articleEmbeddingService
    ) {}

    #[OA\Post(
        path: '/api/articles/search',
        description: 'Searches articles using AI-powered semantic similarity. Returns articles that are semantically similar to the query text.',
        summary: 'Search articles using semantic similarity',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['query'],
                properties: [
                    new OA\Property(
                        property: 'query',
                        description: 'Search query text (3-500 characters)',
                        type: 'string',
                        example: 'How to reset password?'
                    ),
                    new OA\Property(
                        property: 'limit',
                        description: 'Maximum number of results to return (1-50)',
                        type: 'integer',
                        default: 10,
                        maximum: 50,
                        minimum: 1,
                        example: 10
                    ),
                ]
            )
        ),
        tags: ['Articles'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response with matching articles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'public_id', type: 'string', example: 'art_abc123'),
                                    new OA\Property(
                                        property: 'title',
                                        type: 'string',
                                        example: 'How to Reset Your Password'
                                    ),
                                    new OA\Property(property: 'slug', type: 'string', example: 'how-to-reset-password'),
                                    new OA\Property(
                                        property: 'summary',
                                        type: 'string',
                                        example: 'Step-by-step guide to reset your password',
                                        nullable: true
                                    ),
                                    new OA\Property(property: 'language', type: 'string', example: 'en'),
                                    new OA\Property(
                                        property: 'created_at',
                                        type: 'string',
                                        format: 'date-time',
                                        example: '2025-01-15T10:30:00Z'
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The query field is required.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function search(SearchArticleRequest $request): ListResource
    {
        $validated = $request->validated();

        $limit = (int) ($validated['limit'] ?? 10);
        $articles = $this->articleEmbeddingService->search($validated['query'], $limit);

        return new ListResource($articles);
    }
}

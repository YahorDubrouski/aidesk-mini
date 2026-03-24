<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ApiKey;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\ApiKey\StoreApiKeyRequest;
use App\Http\Resources\Api\ApiKey\ListResource;
use App\Http\Resources\Api\ApiKey\ShowResource;
use App\Models\ApiKey;
use App\Services\ApiKey\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

final class ApiKeysController extends Controller
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService
    ) {}

    #[OA\Get(
        path: '/api-keys',
        description: 'Returns API keys for the authenticated user (plain key is never returned after creation).',
        summary: 'List API keys',
        security: [['sanctum' => []]],
        tags: ['API Keys'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of API keys',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'CI bot'),
                                    new OA\Property(property: 'daily_quota', type: 'integer', example: 1000),
                                    new OA\Property(property: 'daily_usage', type: 'integer', example: 42),
                                    new OA\Property(property: 'last_used_at', type: 'string', format: 'date-time', nullable: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): ListResource
    {
        $apiKeys = ApiKey::query()
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return ListResource::make($apiKeys);
    }

    #[OA\Post(
        path: '/api-keys',
        description: 'Creates a key; the plain text secret is returned only in this response (`key` field).',
        summary: 'Create API key',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Production integration'),
                ]
            )
        ),
        tags: ['API Keys'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Key created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'daily_quota', type: 'integer'),
                                new OA\Property(property: 'daily_usage', type: 'integer'),
                                new OA\Property(property: 'last_used_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(
                                    property: 'key',
                                    description: 'Plain secret; shown once',
                                    type: 'string',
                                    example: 'aidesk_xxxxxxxx'
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->apiKeyService->generate(
            Auth::user(),
            $validated['name']
        );

        return (new ShowResource($result['apiKey'], $result['plainKey']))->response()->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api-keys/{apiKey}',
        summary: 'Revoke API key',
        security: [['sanctum' => []]],
        tags: ['API Keys'],
        parameters: [
            new OA\Parameter(
                name: 'apiKey',
                description: 'API key id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Revoked',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'API key revoked successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 403,
                description: 'Key belongs to another user',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
                    ]
                )
            ),
        ]
    )]
    public function destroy(ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->apiKeyService->revoke($apiKey);

        return response()->json(['message' => 'API key revoked successfully']);
    }
}

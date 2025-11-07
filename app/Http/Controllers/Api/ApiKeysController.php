<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ApiKey\StoreApiKeyRequest;
use App\Http\Resources\Api\ApiKey\ShowResource;
use App\Http\Resources\Api\ApiKey\ListResource;
use App\Models\ApiKey;
use App\Services\ApiKey\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class ApiKeysController extends Controller
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService
    ) {
    }

    public function index(): ListResource
    {
        $apiKeys = ApiKey::query()
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return ListResource::make($apiKeys);
    }

    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->apiKeyService->generate(
            Auth::user(),
            $validated['name']
        );

        return (new ShowResource($result['apiKey'], $result['plainKey']))->response()->setStatusCode(201);
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->apiKeyService->revoke($apiKey);

        return response()->json(['message' => 'API key revoked successfully']);
    }
}

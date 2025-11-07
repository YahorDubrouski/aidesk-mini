<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ApiKey\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiKeyMiddleware
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $keyHeader = $request->header('X-API-Key');

        if (!$keyHeader) {
            return response()->json(['message' => 'API key required'], 401);
        }

        $apiKey = $this->apiKeyService->validate($keyHeader);

        if (!$apiKey) {
            return response()->json(['message' => 'Invalid API key or quota exceeded'], 401);
        }

        $this->apiKeyService->trackUsage($apiKey);

        $request->setUserResolver(fn () => $apiKey->user);

        return $next($request);
    }
}

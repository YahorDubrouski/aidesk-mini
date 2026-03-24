<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Health;

use App\Http\Controllers\Api\Controller;
use App\Services\AppHealth\HealthCheckService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class HealthController extends Controller
{
    #[OA\Get(
        path: '/health/live',
        description: 'Liveness probe — process is running (no dependency checks).',
        summary: 'Liveness',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Alive',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'service', type: 'string', example: 'AIDesk Mini'),
                                new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                                new OA\Property(property: 'status', type: 'string', example: 'alive'),
                                new OA\Property(property: 'ts', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function live(): JsonResponse
    {
        return response()->json([
            'data' => [
                'service' => config('app.name'),
                'version' => config('app.version'),
                'status' => 'alive',
                'ts' => now()->toIso8601String(),
            ],
        ], 200);
    }

    #[OA\Get(
        path: '/health/ready',
        description: 'Readiness probe — checks DB and other dependencies; returns 503 if degraded.',
        summary: 'Readiness',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ready',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'service', type: 'string'),
                                new OA\Property(property: 'version', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', example: 'ready'),
                                new OA\Property(property: 'checks', type: 'object', example: ['database' => true]),
                                new OA\Property(property: 'ts', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 503,
                description: 'Degraded — one or more checks failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'degraded'),
                                new OA\Property(property: 'checks', type: 'object'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function ready(HealthCheckService $health): JsonResponse
    {
        $checks = $health->readinessChecks();
        $ok = ! in_array(false, $checks, true);

        return response()->json([
            'data' => [
                'service' => config('app.name'),
                'version' => config('app.version'),
                'status' => $ok ? 'ready' : 'degraded',
                'checks' => $checks,
                'ts' => now()->toIso8601String(),
            ],
        ], $ok ? 200 : 503);
    }
}

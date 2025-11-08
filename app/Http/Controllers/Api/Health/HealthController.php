<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Health;

use App\Http\Controllers\Api\Controller;
use App\Services\AppHealth\HealthCheckService;
use Illuminate\Http\JsonResponse;

final class HealthController extends Controller
{
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

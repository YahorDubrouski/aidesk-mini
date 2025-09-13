<?php

namespace App\Services\AppHealth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class HealthCheckService
{
    /**
     * Return readiness booleans for dependencies.
     *
     * @return array{db: bool, redis: bool}
     */
    public function readinessChecks(): array
    {
        $checks = ['db' => false, 'redis' => false];

        try {
            DB::select('SELECT 1'); // fast, read-only
            $checks['db'] = true;
        } catch (Throwable) {
            $checks['db'] = false;
        }

        try {
            $redisConnection = Redis::connection();
            $key = 'health:ping';
            $redisConnection->set($key, '1', 'EX', 5);
            $checks['redis'] = $redisConnection->get($key) === '1';
        } catch (Throwable) {
            $checks['redis'] = false;
        }

        return $checks;
    }
}

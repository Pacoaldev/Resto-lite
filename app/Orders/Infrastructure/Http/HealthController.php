<?php

namespace App\Orders\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $dbOk = $checks['database'] === 'ok';
        $redisOk = $checks['redis'] === 'ok';

        if (!$dbOk) {
            $status = 'down';
            $http = 503;
        } elseif (!$redisOk) {
            $status = 'degraded';
            $http = 200;
        } else {
            $status = 'ok';
            $http = 200;
        }

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $http);
    }

    private function checkDatabase(): string
    {
        try {
            DB::select('SELECT 1');

            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }

    private function checkRedis(): string
    {
        try {
            Redis::connection()->ping();

            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }
}

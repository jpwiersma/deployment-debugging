<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;
use Illuminate\Support\Facades\Cache;

class CacheCheck implements DiagnosticCheck
{
    public function name(): string
    {
        return 'Cache';
    }

    public function run(): array
    {
        $results = [];

        $driver = config('cache.default', 'unknown');
        $results[] = CheckResult::ok('Driver', $driver);

        // SET/GET round-trip
        try {
            $key = 'diagnostics:cache_test';
            $value = 'ping_'.microtime(true);

            $start = microtime(true);
            Cache::put($key, $value, 10);
            $retrieved = Cache::get($key);
            $latency = (microtime(true) - $start) * 1000;

            Cache::forget($key);

            $results[] = $retrieved === $value
                ? CheckResult::ok('SET/GET Round-trip', 'OK', $latency)
                : CheckResult::error('SET/GET Round-trip', 'Value mismatch', $latency);
        } catch (\Throwable $e) {
            $results[] = CheckResult::error('SET/GET Round-trip', $e->getMessage());
        }

        // Redis-specific checks
        if ($driver === 'redis') {
            try {
                $redis = Cache::store('redis')->getStore()->connection();

                $start = microtime(true);
                $pong = $redis->ping();
                $latency = (microtime(true) - $start) * 1000;

                $results[] = $pong
                    ? CheckResult::ok('Redis PING', 'PONG', $latency)
                    : CheckResult::error('Redis PING', 'No response');

                $info = $redis->info();
                if (isset($info['redis_version'])) {
                    $results[] = CheckResult::ok('Redis Version', $info['redis_version']);
                }
                if (isset($info['used_memory_human'])) {
                    $results[] = CheckResult::ok('Redis Memory', $info['used_memory_human']);
                }
                if (isset($info['connected_clients'])) {
                    $results[] = CheckResult::ok('Redis Clients', $info['connected_clients']);
                }
            } catch (\Throwable $e) {
                $results[] = CheckResult::error('Redis', $e->getMessage());
            }
        }

        return $results;
    }
}

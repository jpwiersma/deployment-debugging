<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;

class EnvironmentCheck implements DiagnosticCheck
{
    private const SENSITIVE_PATTERNS = ['PASSWORD', 'SECRET', 'KEY', 'TOKEN', 'PRIVATE', 'CREDENTIAL'];

    public function name(): string
    {
        return 'Environment';
    }

    public function run(): array
    {
        $results = [];

        $env = config('app.env', 'unknown');
        $results[] = $env === 'production'
            ? CheckResult::ok('APP_ENV', $env)
            : CheckResult::warning('APP_ENV', $env.' (not production)');

        $debug = config('app.debug');
        $results[] = $debug
            ? CheckResult::warning('APP_DEBUG', 'true (should be false in production)')
            : CheckResult::ok('APP_DEBUG', 'false');

        $url = config('app.url', 'not set');
        $results[] = $url === 'http://localhost'
            ? CheckResult::warning('APP_URL', $url.' (default)')
            : CheckResult::ok('APP_URL', $url);

        $results[] = config('app.key')
            ? CheckResult::ok('APP_KEY', 'Set')
            : CheckResult::error('APP_KEY', 'Not set');

        $configCached = app()->configurationIsCached();
        $results[] = $configCached
            ? CheckResult::ok('Config Cache', 'Cached')
            : CheckResult::warning('Config Cache', 'Not cached');

        $routesCached = app()->routesAreCached();
        $results[] = $routesCached
            ? CheckResult::ok('Route Cache', 'Cached')
            : CheckResult::warning('Route Cache', 'Not cached');

        // Masked env vars
        $envVars = $this->getSensitiveEnvVars();
        foreach ($envVars as $key => $isSet) {
            $results[] = $isSet
                ? CheckResult::ok($key, '********')
                : CheckResult::warning($key, 'Not set');
        }

        return $results;
    }

    private function getSensitiveEnvVars(): array
    {
        $sensitive = [];

        foreach ($_ENV as $key => $value) {
            foreach (self::SENSITIVE_PATTERNS as $pattern) {
                if (str_contains(strtoupper($key), $pattern)) {
                    $sensitive[$key] = ! empty($value) && $value !== 'null';
                    break;
                }
            }
        }

        return $sensitive;
    }
}

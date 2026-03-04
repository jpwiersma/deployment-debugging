<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;

class WebServerCheck implements DiagnosticCheck
{
    public function name(): string
    {
        return 'Web Server';
    }

    public function run(): array
    {
        $results = [];

        // Server software
        $server = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown (CLI)';
        $results[] = CheckResult::ok('Server Software', $server);

        // HTTPS
        $isHttps = $this->isHttps();
        $results[] = $isHttps
            ? CheckResult::ok('HTTPS', 'Active')
            : CheckResult::warning('HTTPS', 'Not detected');

        // APP_URL protocol match
        $appUrl = config('app.url', '');
        if ($appUrl) {
            $urlIsHttps = str_starts_with($appUrl, 'https://');
            if ($isHttps && ! $urlIsHttps) {
                $results[] = CheckResult::warning('APP_URL Protocol', "HTTPS active but APP_URL is HTTP ({$appUrl})");
            } elseif (! $isHttps && $urlIsHttps) {
                $results[] = CheckResult::warning('APP_URL Protocol', "APP_URL is HTTPS but connection is HTTP");
            } else {
                $results[] = CheckResult::ok('APP_URL Protocol', 'Matches connection');
            }
        }

        // Request host vs APP_URL
        $requestHost = request()->getHost();
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        if ($requestHost && $appHost) {
            $results[] = $requestHost === $appHost
                ? CheckResult::ok('Host Match', "{$requestHost} matches APP_URL")
                : CheckResult::warning('Host Match', "Request host ({$requestHost}) differs from APP_URL ({$appHost})");
        }

        // Trusted proxies / forwarded headers
        $forwardedFor = request()->header('X-Forwarded-For');
        $forwardedProto = request()->header('X-Forwarded-Proto');

        if ($forwardedFor || $forwardedProto) {
            $results[] = CheckResult::ok('Proxy Headers', 'X-Forwarded-For: '.($forwardedFor ?: 'none').', X-Forwarded-Proto: '.($forwardedProto ?: 'none'));
        } else {
            $results[] = CheckResult::ok('Proxy Headers', 'None detected (direct connection)');
        }

        return $results;
    }

    private function isHttps(): bool
    {
        return request()->secure()
            || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (request()->header('X-Forwarded-Proto') === 'https');
    }
}

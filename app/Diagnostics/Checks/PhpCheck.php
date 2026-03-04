<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;

class PhpCheck implements DiagnosticCheck
{
    public function name(): string
    {
        return 'PHP';
    }

    public function run(): array
    {
        $results = [];

        $results[] = CheckResult::ok('Version', PHP_VERSION);
        $results[] = CheckResult::ok('SAPI', PHP_SAPI);
        $results[] = CheckResult::ok('OS', PHP_OS.' '.php_uname('r'));

        $memoryLimit = ini_get('memory_limit');
        $results[] = CheckResult::ok('Memory Limit', $memoryLimit);

        // Required extensions
        $required = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
        $missing = array_filter($required, fn (string $ext) => ! extension_loaded($ext));

        $results[] = empty($missing)
            ? CheckResult::ok('Required Extensions', 'All loaded ('.implode(', ', $required).')')
            : CheckResult::error('Required Extensions', 'Missing: '.implode(', ', $missing));

        // OPcache
        if (function_exists('opcache_get_status')) {
            $opcache = @opcache_get_status(false);
            $results[] = $opcache && ($opcache['opcache_enabled'] ?? false)
                ? CheckResult::ok('OPcache', 'Enabled — '.$opcache['opcache_statistics']['num_cached_scripts'].' scripts cached')
                : CheckResult::warning('OPcache', 'Disabled');
        } else {
            $results[] = CheckResult::warning('OPcache', 'Extension not loaded');
        }

        // Optional extensions
        $optional = ['redis', 'imagick', 'gd', 'curl', 'zip', 'intl'];
        $loaded = array_filter($optional, fn (string $ext) => extension_loaded($ext));
        $results[] = CheckResult::ok('Optional Extensions', implode(', ', $loaded) ?: 'None');

        return $results;
    }
}

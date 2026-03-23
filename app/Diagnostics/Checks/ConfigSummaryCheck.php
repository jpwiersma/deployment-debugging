<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;

class ConfigSummaryCheck implements DiagnosticCheck
{
    public function name(): string
    {
        return 'Config Summary (Strackt reads these)';
    }

    public function run(): array
    {
        $results = [];

        $composer = $this->readComposerJson();

        // PHP constraint from composer.json
        $phpConstraint = $composer['require']['php'] ?? 'not set';
        $results[] = CheckResult::ok('composer.json → require.php', $phpConstraint);

        // Extensions from composer.json
        $extensions = $this->getComposerExtensions($composer);
        $results[] = CheckResult::ok(
            'composer.json → extensions',
            $extensions ? implode(', ', $extensions) : 'none'
        );

        // Notable packages
        $notablePackages = ['laravel/framework', 'laravel/horizon', 'statamic/cms', 'predis/predis'];
        $found = [];
        foreach ($notablePackages as $package) {
            if (isset($composer['require'][$package])) {
                $found[] = $package.':'.$composer['require'][$package];
            }
        }
        $results[] = CheckResult::ok(
            'composer.json → notable packages',
            $found ? implode(', ', $found) : 'none beyond laravel/framework'
        );

        // Config file defaults (what Strackt regex-parses)
        $results[] = CheckResult::ok(
            'config/database.php → default',
            $this->parseConfigDefault(config_path('database.php'), 'DB_CONNECTION') ?? 'not found'
        );

        $results[] = CheckResult::ok(
            'config/cache.php → default',
            $this->parseConfigDefault(config_path('cache.php'), 'CACHE_STORE') ?? 'not found'
        );

        $results[] = CheckResult::ok(
            'config/queue.php → default',
            $this->parseConfigDefault(config_path('queue.php'), 'QUEUE_CONNECTION') ?? 'not found'
        );

        // Scheduler detection
        $consoleRoutes = base_path('routes/console.php');
        $hasSchedule = false;
        if (file_exists($consoleRoutes)) {
            $content = file_get_contents($consoleRoutes);
            $hasSchedule = str_contains($content, 'Schedule::') || str_contains($content, '->schedule(');
        }
        $results[] = CheckResult::ok(
            'routes/console.php → Schedule',
            $hasSchedule ? 'yes (scheduler detected)' : 'no'
        );

        // package.json engines
        $packageJson = $this->readPackageJson();
        $nodeEngine = $packageJson['engines']['node'] ?? null;
        $results[] = CheckResult::ok(
            'package.json → engines.node',
            $nodeEngine ?? 'not set (Strackt uses default)'
        );

        return $results;
    }

    private function readComposerJson(): array
    {
        $path = base_path('composer.json');
        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    private function readPackageJson(): array
    {
        $path = base_path('package.json');
        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    private function getComposerExtensions(array $composer): array
    {
        $extensions = [];
        foreach (array_keys($composer['require'] ?? []) as $key) {
            if (str_starts_with($key, 'ext-')) {
                $extensions[] = $key;
            }
        }

        return $extensions;
    }

    /**
     * Parse config file to extract the fallback value from env() calls.
     * Mimics what Strackt's detectors do: regex for env('KEY', 'fallback').
     */
    private function parseConfigDefault(string $filePath, string $envKey): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);

        if (preg_match("/env\(\s*'{$envKey}'\s*,\s*'([^']+)'\s*\)/", $content, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

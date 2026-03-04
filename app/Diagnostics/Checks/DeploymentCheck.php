<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;

class DeploymentCheck implements DiagnosticCheck
{
    public function name(): string
    {
        return 'Deployment';
    }

    public function run(): array
    {
        $results = [];

        // Git info
        $gitSha = $this->exec('git rev-parse --short HEAD');
        $results[] = $gitSha
            ? CheckResult::ok('Git SHA', $gitSha)
            : CheckResult::warning('Git SHA', 'Not available');

        $gitBranch = $this->exec('git rev-parse --abbrev-ref HEAD');
        $results[] = $gitBranch
            ? CheckResult::ok('Git Branch', $gitBranch)
            : CheckResult::warning('Git Branch', 'Not available');

        // Laravel version
        $results[] = CheckResult::ok('Laravel Version', app()->version());

        // Composer packages
        $lockFile = base_path('composer.lock');
        if (file_exists($lockFile)) {
            $lock = json_decode(file_get_contents($lockFile), true);
            $packageCount = count($lock['packages'] ?? []);
            $devCount = count($lock['packages-dev'] ?? []);
            $results[] = CheckResult::ok('Composer Packages', "{$packageCount} prod, {$devCount} dev");
        } else {
            $results[] = CheckResult::warning('Composer Lock', 'composer.lock not found');
        }

        // Node/npm
        $nodeVersion = $this->exec('node --version');
        $results[] = $nodeVersion
            ? CheckResult::ok('Node.js', $nodeVersion)
            : CheckResult::warning('Node.js', 'Not available');

        $npmVersion = $this->exec('npm --version');
        $results[] = $npmVersion
            ? CheckResult::ok('npm', 'v'.$npmVersion)
            : CheckResult::warning('npm', 'Not available');

        // Vite manifest
        $manifestPath = public_path('build/.vite/manifest.json');
        if (! file_exists($manifestPath)) {
            $manifestPath = public_path('build/manifest.json');
        }

        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $entryCount = is_array($manifest) ? count($manifest) : 0;
            $results[] = CheckResult::ok('Vite Manifest', "{$entryCount} entries");
        } else {
            $results[] = CheckResult::warning('Vite Manifest', 'Not found (run npm run build)');
        }

        return $results;
    }

    private function exec(string $command): ?string
    {
        try {
            $output = @shell_exec($command.' 2>/dev/null');

            return $output ? trim($output) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

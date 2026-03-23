<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;
use Composer\InstalledVersions;
use Composer\Semver\Semver;

class ComposerRequirementsCheck implements DiagnosticCheck
{
    public function name(): string
    {
        return 'Composer Requirements';
    }

    public function run(): array
    {
        $results = [];

        $composer = $this->readComposerJson();
        if (empty($composer)) {
            $results[] = CheckResult::error('composer.json', 'File not found or invalid');

            return $results;
        }

        $require = $composer['require'] ?? [];

        // PHP version: declared constraint vs actual
        if (isset($require['php'])) {
            $constraint = $require['php'];
            $actual = PHP_VERSION;

            try {
                $satisfied = Semver::satisfies($actual, $constraint);
                $results[] = $satisfied
                    ? CheckResult::ok('PHP Version', "{$constraint} → {$actual}")
                    : CheckResult::error('PHP Version', "{$constraint} → {$actual} (not satisfied)");
            } catch (\Throwable) {
                $results[] = CheckResult::warning('PHP Version', "{$constraint} → {$actual} (could not validate)");
            }
        } else {
            $results[] = CheckResult::warning('PHP Version', 'No constraint in composer.json');
        }

        // PHP extensions: ext-* requirements vs loaded
        foreach ($require as $key => $version) {
            if (! str_starts_with($key, 'ext-')) {
                continue;
            }

            $extName = substr($key, 4);
            $loaded = extension_loaded($extName);
            $results[] = $loaded
                ? CheckResult::ok($key, 'Loaded')
                : CheckResult::error($key, 'Not loaded');
        }

        // Key packages: presence + installed version
        $notablePackages = [
            'laravel/framework' => 'Laravel Framework',
            'laravel/horizon' => 'Laravel Horizon',
            'statamic/cms' => 'Statamic CMS',
            'predis/predis' => 'Predis',
            'livewire/livewire' => 'Livewire',
        ];

        foreach ($notablePackages as $package => $label) {
            if (! isset($require[$package])) {
                continue;
            }

            try {
                if (InstalledVersions::isInstalled($package)) {
                    $installedVersion = InstalledVersions::getPrettyVersion($package);
                    $results[] = CheckResult::ok($label, "Required {$require[$package]} → installed {$installedVersion}");
                } else {
                    $results[] = CheckResult::error($label, "Required {$require[$package]} → not installed");
                }
            } catch (\Throwable) {
                $results[] = CheckResult::warning($label, "Required {$require[$package]} → could not check");
            }
        }

        // Node engines constraint vs actual
        $packageJson = $this->readPackageJson();
        $nodeConstraint = $packageJson['engines']['node'] ?? null;

        if ($nodeConstraint) {
            $nodeVersion = $this->getNodeVersion();
            if ($nodeVersion) {
                $results[] = CheckResult::ok('Node.js Engine', "{$nodeConstraint} → {$nodeVersion}");
            } else {
                $results[] = CheckResult::warning('Node.js Engine', "{$nodeConstraint} → node not available");
            }
        }

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

    private function getNodeVersion(): ?string
    {
        try {
            $output = @shell_exec('node --version 2>/dev/null');

            return $output ? trim($output) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

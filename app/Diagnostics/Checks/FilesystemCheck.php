<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;
use Illuminate\Support\Facades\Storage;

class FilesystemCheck implements DiagnosticCheck
{
    public function name(): string
    {
        return 'Filesystem';
    }

    public function run(): array
    {
        $results = [];

        $disk = config('filesystems.default', 'unknown');
        $results[] = CheckResult::ok('Default Disk', $disk);

        // Write test
        try {
            $path = 'diagnostics/test_'.time().'.txt';
            Storage::put($path, 'diagnostic write test');
            $content = Storage::get($path);
            Storage::delete($path);

            $results[] = $content === 'diagnostic write test'
                ? CheckResult::ok('Storage Write/Read', 'OK')
                : CheckResult::error('Storage Write/Read', 'Content mismatch');
        } catch (\Throwable $e) {
            $results[] = CheckResult::error('Storage Write/Read', $e->getMessage());
        }

        // Disk space
        $storagePath = storage_path();
        $free = @disk_free_space($storagePath);
        $total = @disk_total_space($storagePath);

        if ($free !== false && $total !== false) {
            $usedPct = round(($total - $free) / $total * 100, 1);
            $freeHuman = $this->humanFileSize($free);
            $totalHuman = $this->humanFileSize($total);

            $results[] = $usedPct > 90
                ? CheckResult::warning('Disk Space', "{$usedPct}% used ({$freeHuman} free of {$totalHuman})")
                : CheckResult::ok('Disk Space', "{$usedPct}% used ({$freeHuman} free of {$totalHuman})");
        } else {
            $results[] = CheckResult::warning('Disk Space', 'Unable to determine');
        }

        // Public storage symlink
        $symlinkPath = public_path('storage');
        $results[] = is_link($symlinkPath)
            ? CheckResult::ok('Storage Symlink', 'public/storage linked')
            : CheckResult::warning('Storage Symlink', 'Not linked (run php artisan storage:link)');

        // Log write test
        try {
            $logPath = storage_path('logs');
            $results[] = is_writable($logPath)
                ? CheckResult::ok('Log Directory', 'Writable')
                : CheckResult::error('Log Directory', 'Not writable');
        } catch (\Throwable $e) {
            $results[] = CheckResult::error('Log Directory', $e->getMessage());
        }

        return $results;
    }

    private function humanFileSize(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}

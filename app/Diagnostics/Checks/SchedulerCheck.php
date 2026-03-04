<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;
use Illuminate\Support\Facades\Cache;

class SchedulerCheck implements DiagnosticCheck
{
    public function name(): string
    {
        return 'Scheduler';
    }

    public function run(): array
    {
        $results = [];

        // Heartbeat
        $lastRun = Cache::get('diagnostics:last_cron_run');

        if ($lastRun) {
            $diffMinutes = now()->diffInMinutes($lastRun);
            $ago = now()->diffForHumans($lastRun);
            $formatted = $lastRun->format('Y-m-d H:i:s');

            if ($diffMinutes <= 2) {
                $results[] = CheckResult::ok('Heartbeat', "{$formatted} ({$ago})");
            } elseif ($diffMinutes <= 10) {
                $results[] = CheckResult::warning('Heartbeat', "{$formatted} ({$ago}) — may be delayed");
            } else {
                $results[] = CheckResult::error('Heartbeat', "{$formatted} ({$ago}) — scheduler appears stopped");
            }
        } else {
            $results[] = CheckResult::warning('Heartbeat', 'No heartbeat recorded (run php artisan schedule:run)');
        }

        // Schedule cache
        $scheduleCacheFile = base_path('bootstrap/cache/schedule-*.php');
        $cacheFiles = glob($scheduleCacheFile);
        $results[] = ! empty($cacheFiles)
            ? CheckResult::ok('Schedule Cache', 'Cached')
            : CheckResult::ok('Schedule Cache', 'Not cached');

        return $results;
    }
}

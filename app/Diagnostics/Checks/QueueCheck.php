<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;
use App\Jobs\PingJob;
use Illuminate\Support\Facades\Cache;

class QueueCheck implements DiagnosticCheck
{
    public function name(): string
    {
        return 'Queue';
    }

    public function run(): array
    {
        $results = [];

        $driver = config('queue.default', 'unknown');
        $results[] = CheckResult::ok('Driver', $driver);

        // Failed jobs count
        try {
            $failedCount = app('queue.failer')->count();
            $results[] = $failedCount === 0
                ? CheckResult::ok('Failed Jobs', '0')
                : CheckResult::warning('Failed Jobs', (string) $failedCount);
        } catch (\Throwable) {
            $results[] = CheckResult::warning('Failed Jobs', 'Unable to query');
        }

        // Dispatch PingJob
        try {
            PingJob::dispatch();
            $results[] = CheckResult::ok('PingJob Dispatch', 'Dispatched successfully');
        } catch (\Throwable $e) {
            $results[] = CheckResult::error('PingJob Dispatch', $e->getMessage());
        }

        // Check last PingJob processed
        $lastPing = Cache::get('diagnostics:ping_job_last_run');
        if ($lastPing) {
            $ago = now()->diffForHumans($lastPing);
            $results[] = CheckResult::ok('PingJob Last Processed', $lastPing->format('Y-m-d H:i:s')." ({$ago})");
        } else {
            $results[] = CheckResult::warning('PingJob Last Processed', 'Never (run queue:work to process)');
        }

        // Horizon check
        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            try {
                $status = Cache::get('horizon:status', 'unknown');
                $results[] = $status === 'running'
                    ? CheckResult::ok('Horizon', 'Running')
                    : CheckResult::warning('Horizon', $status);
            } catch (\Throwable) {
                $results[] = CheckResult::warning('Horizon', 'Unable to check status');
            }
        }

        return $results;
    }
}

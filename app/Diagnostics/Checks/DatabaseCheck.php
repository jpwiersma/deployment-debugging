<?php

namespace App\Diagnostics\Checks;

use App\Diagnostics\CheckResult;
use App\Diagnostics\Contracts\DiagnosticCheck;
use Illuminate\Support\Facades\DB;

class DatabaseCheck implements DiagnosticCheck
{
    public function name(): string
    {
        return 'Database';
    }

    public function run(): array
    {
        $results = [];

        $driver = config('database.default', 'unknown');
        $results[] = CheckResult::ok('Driver', $driver);

        try {
            $start = microtime(true);
            $pdo = DB::connection()->getPdo();
            $latency = (microtime(true) - $start) * 1000;

            $results[] = CheckResult::ok('Connection', 'Connected', $latency);

            // Version
            $version = match ($driver) {
                'mysql' => DB::selectOne('SELECT VERSION() as v')?->v ?? 'unknown',
                'pgsql' => DB::selectOne('SELECT version() as v')?->v ?? 'unknown',
                'sqlite' => DB::selectOne('SELECT sqlite_version() as v')?->v ?? 'unknown',
                default => 'N/A',
            };
            $results[] = CheckResult::ok('Server Version', $version);

            // Database name
            $dbName = match ($driver) {
                'sqlite' => config('database.connections.sqlite.database', 'unknown'),
                default => DB::getDatabaseName(),
            };
            $results[] = CheckResult::ok('Database', $dbName);

            // Charset (MySQL/Postgres)
            if ($driver === 'mysql') {
                $charset = DB::selectOne("SHOW VARIABLES LIKE 'character_set_database'")?->Value ?? 'unknown';
                $collation = DB::selectOne("SHOW VARIABLES LIKE 'collation_database'")?->Value ?? 'unknown';
                $results[] = CheckResult::ok('Charset', "{$charset} / {$collation}");
            }
        } catch (\Throwable $e) {
            $results[] = CheckResult::error('Connection', $e->getMessage());
        }

        return $results;
    }
}

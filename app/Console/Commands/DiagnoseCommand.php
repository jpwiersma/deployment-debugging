<?php

namespace App\Console\Commands;

use App\Diagnostics\DiagnosticRunner;
use App\Diagnostics\Enums\Status;
use Illuminate\Console\Command;

class DiagnoseCommand extends Command
{
    protected $signature = 'diagnose';

    protected $description = 'Run deployment diagnostics and display results';

    public function handle(DiagnosticRunner $runner): int
    {
        $this->components->info('Running deployment diagnostics...');
        $this->newLine();

        $results = $runner->run();
        $summary = $runner->summary($results);

        foreach ($results as $name => $checks) {
            $this->components->twoColumnDetail(
                "<fg=white;options=bold>{$name}</>",
                ''
            );

            foreach ($checks as $result) {
                $statusColor = match ($result->status) {
                    Status::Ok => 'green',
                    Status::Warning => 'yellow',
                    Status::Error => 'red',
                };

                $icon = $result->status->icon();
                $detail = $result->detail;

                if ($result->latencyMs !== null) {
                    $detail .= ' ('.number_format($result->latencyMs, 1).'ms)';
                }

                $this->components->twoColumnDetail(
                    "  {$result->label}",
                    "<fg={$statusColor}>{$icon} {$detail}</>"
                );
            }

            $this->newLine();
        }

        $summaryColor = match ($summary) {
            Status::Ok => 'green',
            Status::Warning => 'yellow',
            Status::Error => 'red',
        };

        $this->components->twoColumnDetail(
            '<fg=white;options=bold>Overall Status</>',
            "<fg={$summaryColor};options=bold>{$summary->icon()} {$summary->label()}</>"
        );

        $this->newLine();

        return $summary === Status::Error ? self::FAILURE : self::SUCCESS;
    }
}

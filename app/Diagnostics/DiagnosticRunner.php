<?php

namespace App\Diagnostics;

use App\Diagnostics\Contracts\DiagnosticCheck;
use App\Diagnostics\Enums\Status;

class DiagnosticRunner
{
    /** @var array<DiagnosticCheck> */
    private array $checks = [];

    public function register(DiagnosticCheck $check): self
    {
        $this->checks[] = $check;

        return $this;
    }

    /** @return array<string, array<CheckResult>> */
    public function run(): array
    {
        $results = [];

        foreach ($this->checks as $check) {
            try {
                $results[$check->name()] = $check->run();
            } catch (\Throwable $e) {
                $results[$check->name()] = [
                    CheckResult::error($check->name(), 'Check failed: '.$e->getMessage()),
                ];
            }
        }

        return $results;
    }

    public function summary(array $results): Status
    {
        foreach ($results as $group) {
            foreach ($group as $result) {
                if ($result->status === Status::Error) {
                    return Status::Error;
                }
            }
        }

        foreach ($results as $group) {
            foreach ($group as $result) {
                if ($result->status === Status::Warning) {
                    return Status::Warning;
                }
            }
        }

        return Status::Ok;
    }
}

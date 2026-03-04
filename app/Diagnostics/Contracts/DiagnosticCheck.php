<?php

namespace App\Diagnostics\Contracts;

use App\Diagnostics\CheckResult;

interface DiagnosticCheck
{
    /** @return array<CheckResult> */
    public function run(): array;

    public function name(): string;
}

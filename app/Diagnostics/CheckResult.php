<?php

namespace App\Diagnostics;

use App\Diagnostics\Enums\Status;

readonly class CheckResult
{
    public function __construct(
        public Status $status,
        public string $label,
        public string $detail,
        public ?float $latencyMs = null,
    ) {}

    public static function ok(string $label, string $detail, ?float $latencyMs = null): self
    {
        return new self(Status::Ok, $label, $detail, $latencyMs);
    }

    public static function warning(string $label, string $detail, ?float $latencyMs = null): self
    {
        return new self(Status::Warning, $label, $detail, $latencyMs);
    }

    public static function error(string $label, string $detail, ?float $latencyMs = null): self
    {
        return new self(Status::Error, $label, $detail, $latencyMs);
    }
}

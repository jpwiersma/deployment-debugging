<?php

namespace App\Diagnostics\Enums;

enum Status: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Error = 'error';

    public function color(): string
    {
        return match ($this) {
            self::Ok => 'green',
            self::Warning => 'yellow',
            self::Error => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Ok => "\u{2713}",
            self::Warning => "\u{26A0}",
            self::Error => "\u{2717}",
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Warning => 'Warning',
            self::Error => 'Error',
        };
    }
}

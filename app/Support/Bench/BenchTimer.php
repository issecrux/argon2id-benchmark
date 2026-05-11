<?php

namespace App\Support\Bench;

final class BenchTimer
{
    public static function now(): int
    {
        return hrtime(true);
    }

    public static function millisecondsSince(int $start): float
    {
        return round((hrtime(true) - $start) / 1_000_000, 6);
    }
}

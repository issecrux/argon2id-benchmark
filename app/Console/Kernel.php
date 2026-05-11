<?php

namespace App\Console;

use App\Console\Commands\BenchRun;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

final class Kernel extends ConsoleKernel
{
    protected $commands = [
        BenchRun::class,
    ];
}

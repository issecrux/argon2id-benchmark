<?php

use App\Support\Bench\BenchTimer;
use App\Support\Bench\ScenarioRepository;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$scenarioId = $argv[1] ?? 'S1';
$repository = new ScenarioRepository;
$scenario = $repository->find($scenarioId, includeUnsafe: true);

if ($scenario === null) {
    fwrite(STDERR, "Unknown scenario: {$scenarioId}".PHP_EOL);
    exit(1);
}

if (! in_array(PASSWORD_ARGON2ID, password_algos(), true)) {
    fwrite(STDERR, 'Argon2id not supported in current PHP build.'.PHP_EOL);
    exit(1);
}

$iterations = (int) ($argv[2] ?? 10);
$times = [];

for ($warmup = 0; $warmup < 1; $warmup++) {
    password_hash('Pa$$w0rd!', PASSWORD_ARGON2ID, $scenario->passwordHashOptions());
}

for ($i = 0; $i < $iterations; $i++) {
    $start = BenchTimer::now();
    $hash = password_hash('Pa$$w0rd!', PASSWORD_ARGON2ID, $scenario->passwordHashOptions());
    $times[] = BenchTimer::millisecondsSince($start);
}

sort($times);
$mean = array_sum($times) / count($times);
$mid = (int) (count($times) / 2);
$median = count($times) % 2 === 0
    ? ($times[$mid - 1] + $times[$mid]) / 2
    : $times[$mid];

echo json_encode([
    'scenario' => $scenario->id,
    'mem_kib' => $scenario->memoryCost,
    't_cost' => $scenario->timeCost,
    'threads' => $scenario->threads,
    'iterations' => $iterations,
    'times_ms' => $times,
    'stats' => [
        'mean_ms' => round($mean, 4),
        'median_ms' => round($median, 4),
        'min_ms' => round(min($times), 4),
        'max_ms' => round(max($times), 4),
        'std_dev_ms' => round(sqrt(array_sum(array_map(fn ($t) => ($t - $mean) ** 2, $times)) / count($times)), 4),
    ],
    'hash_len' => strlen($hash),
], JSON_PRETTY_PRINT).PHP_EOL;

<?php

require __DIR__.'/../vendor/autoload.php';

use App\Support\Bench\BenchTimer;
use App\Support\Bench\ScenarioRepository;

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

$start = BenchTimer::now();
$hash = password_hash('Pa$$w0rd!', PASSWORD_ARGON2ID, $scenario->passwordHashOptions());
$hashMs = BenchTimer::millisecondsSince($start);

echo json_encode([
    'scenario' => $scenario->id,
    'mem_kib' => $scenario->memoryCost,
    't_cost' => $scenario->timeCost,
    'threads' => $scenario->threads,
    't_hash_ms' => $hashMs,
    'hash_len' => strlen($hash),
], JSON_PRETTY_PRINT).PHP_EOL;

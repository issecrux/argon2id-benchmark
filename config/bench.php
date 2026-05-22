<?php

return [
    'demo_user' => [
        'name' => 'Benchmark User',
        'email' => 'user@example.com',
        'password' => 'Pa$$w0rd!',
    ],

    'iterations' => env('BENCH_ITERATIONS', 10),
    'warmup_iterations' => env('BENCH_WARMUP_ITERATIONS', 1),
    'max_memory_kib' => env('BENCH_MAX_MEMORY_KIB', 1572864),
    'results_path' => env('BENCH_RESULTS_PATH', 'results/results.csv'),
];

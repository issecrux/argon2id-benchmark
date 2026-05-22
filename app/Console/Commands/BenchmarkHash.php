<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BenchmarkHash extends Command
{
    protected $signature = 'benchmark:hash
                            {--iterations=30 : Jumlah iterasi per skenario}
                            {--warmup=3 : Jumlah warm-up run sebelum pengukuran}
                            {--export= : Path file CSV untuk export hasil}
                            {--scenario= : Jalankan skenario tertentu saja (label)}';

    protected $description = 'Micro benchmark Argon2id - pure PHP password_hash(), tanpa HTTP overhead';

    // Skenario parameter penelitian berdasarkan RFC 9106 + variasi
    private array $scenarios = [
        // Baseline RFC 9106 second recommended (untuk hardware terbatas)
        ['label' => 'RFC9106-Second', 'memory' => 65536,  'time' => 3, 'threads' => 4],
        // Variasi memory_cost (time=3, parallelism=4 tetap)
        ['label' => 'M1024-T3-P4',   'memory' => 1024,   'time' => 3, 'threads' => 4],
        ['label' => 'M2048-T3-P4',   'memory' => 2048,   'time' => 3, 'threads' => 4],
        ['label' => 'M4096-T3-P4',   'memory' => 4096,   'time' => 3, 'threads' => 4],
        ['label' => 'M8192-T3-P4',   'memory' => 8192,   'time' => 3, 'threads' => 4],
        ['label' => 'M16384-T3-P4',  'memory' => 16384,  'time' => 3, 'threads' => 4],
        ['label' => 'M32768-T3-P4',  'memory' => 32768,  'time' => 3, 'threads' => 4],
        ['label' => 'M65536-T3-P4',  'memory' => 65536,  'time' => 3, 'threads' => 4],
        // Variasi time_cost (memory=65536, parallelism=4 tetap)
        ['label' => 'M65536-T1-P4',  'memory' => 65536,  'time' => 1, 'threads' => 4],
        ['label' => 'M65536-T2-P4',  'memory' => 65536,  'time' => 2, 'threads' => 4],
        ['label' => 'M65536-T4-P4',  'memory' => 65536,  'time' => 4, 'threads' => 4],
        // Variasi parallelism (memory=65536, time=3 tetap)
        ['label' => 'M65536-T3-P1',  'memory' => 65536,  'time' => 3, 'threads' => 1],
        ['label' => 'M65536-T3-P2',  'memory' => 65536,  'time' => 3, 'threads' => 2],
    ];

    public function handle(): int
    {
        $iterations = (int) $this->option('iterations');
        $warmup = (int) $this->option('warmup');
        $exportPath = $this->option('export');
        $onlyLabel = $this->option('scenario');

        $scenarios = $onlyLabel
            ? array_values(array_filter($this->scenarios, fn ($s) => $s['label'] === $onlyLabel))
            : $this->scenarios;

        if (empty($scenarios)) {
            $this->error("Skenario '{$onlyLabel}' tidak ditemukan.");

            return self::FAILURE;
        }

        $this->info('=== Argon2id Micro Benchmark ===');
        $this->info('PHP '.PHP_VERSION.' | Iterations: '.$iterations.' | Warm-up: '.$warmup);
        $this->newLine();

        $results = [];
        $password = 'BenchmarkPassword123!';

        foreach ($scenarios as $scenario) {
            $this->line("  Running: {$scenario['label']} (M={$scenario['memory']} T={$scenario['time']} P={$scenario['threads']})");

            // Warm-up runs - tidak dihitung ke statistik
            for ($i = 0; $i < $warmup; $i++) {
                password_hash($password, PASSWORD_ARGON2ID, [
                    'memory_cost' => $scenario['memory'],
                    'time_cost' => $scenario['time'],
                    'threads' => $scenario['threads'],
                ]);
            }

            // Pengukuran aktual
            $times = [];
            for ($i = 0; $i < $iterations; $i++) {
                $start = hrtime(true);
                password_hash($password, PASSWORD_ARGON2ID, [
                    'memory_cost' => $scenario['memory'],
                    'time_cost' => $scenario['time'],
                    'threads' => $scenario['threads'],
                ]);
                $times[] = (hrtime(true) - $start) / 1e6;
            }

            sort($times);
            $mean = array_sum($times) / count($times);
            $variance = array_sum(array_map(fn ($t) => ($t - $mean) ** 2, $times)) / count($times);
            $mid = (int) ($iterations / 2);
            $median = $iterations % 2 === 0
                ? ($times[$mid - 1] + $times[$mid]) / 2
                : $times[$mid];

            $results[] = [
                'label' => $scenario['label'],
                'memory' => $scenario['memory'],
                'time' => $scenario['time'],
                'threads' => $scenario['threads'],
                'mean_ms' => round($mean, 4),
                'median_ms' => round($median, 4),
                'min_ms' => round(min($times), 4),
                'max_ms' => round(max($times), 4),
                'std_dev_ms' => round(sqrt($variance), 4),
            ];
        }

        $this->newLine();
        $this->table(
            ['Label', 'Memory(KB)', 'Time', 'P', 'Mean(ms)', 'Median(ms)', 'Min(ms)', 'Max(ms)', 'StdDev(ms)'],
            array_map(fn ($r) => [
                $r['label'], $r['memory'], $r['time'], $r['threads'],
                $r['mean_ms'], $r['median_ms'], $r['min_ms'], $r['max_ms'], $r['std_dev_ms'],
            ], $results)
        );

        if ($exportPath) {
            $this->exportCsv($results, $exportPath);
            $this->info("Hasil diekspor ke: {$exportPath}");
        }

        return self::SUCCESS;
    }

    private function exportCsv(array $results, string $path): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, ['label', 'memory_cost', 'time_cost', 'parallelism', 'mean_ms', 'median_ms', 'min_ms', 'max_ms', 'std_dev_ms']);
        foreach ($results as $row) {
            fputcsv($handle, [
                $row['label'], $row['memory'], $row['time'], $row['threads'],
                $row['mean_ms'], $row['median_ms'], $row['min_ms'], $row['max_ms'], $row['std_dev_ms'],
            ]);
        }
        fclose($handle);
    }
}

<?php

/**
 * =============================================================================
 * BATCH RUNNER: Menjalankan Semua Skenario Benchmark Argon2id
 * =============================================================================
 *
 * Script ini menjalankan seluruh 16 skenario benchmark yang didefinisikan
 * dalam SCENARIOS.md, mengumpulkan hasilnya, dan menyimpan konsolidasi
 * dalam format JSON dan CSV.
 *
 * Penggunaan:
 *   php benchmark/run_all_scenarios.php                     # Jalankan semua skenario
 *   php benchmark/run_all_scenarios.php --scenario=3        # Jalankan skenario S3 saja
 *   php benchmark/run_all_scenarios.php --iterations=20     # Gunakan 20 iterasi
 *   php benchmark/run_all_scenarios.php --group=A           # Jalankan semua skenario Group A
 *
 * Output:
 *   benchmark/results/all_scenarios.json   — Hasil lengkap semua skenario
 *   benchmark/results/summary.csv          — Tabel ringkasan komparatif
 *
 * @author  Skripsi Argon2id Auth
 * @version 1.0
 * @date    2026-05-22
 */

// =============================================================================
// DEFINISI 16 SKENARIO BENCHMARK
// =============================================================================

/**
 * Setiap skenario didefinisikan sebagai array asosiatif dengan kunci:
 *   - id         : ID skenario (S1–S16)
 *   - group      : Grup pengujian (A, B, C, D)
 *   - memory     : memory_cost dalam KiB
 *   - time       : time_cost (iterasi Argon2)
 *   - threads    : parallelism (jumlah thread)
 *   - label      : Label deskriptif
 *   - note       : Keterangan tambahan (opsional)
 */
$SCENARIOS = [
    // =========================================================================
    // Group A: Variasi Memory Cost (fix time=3, threads=4)
    // =========================================================================
    [
        'id'      => 'S1',
        'group'   => 'A',
        'memory'  => 16384,
        'time'    => 3,
        'threads' => 4,
        'label'   => 'M16384-T3-P4',
        'note'    => 'Memori minimum rendah',
    ],
    [
        'id'      => 'S2',
        'group'   => 'A',
        'memory'  => 32768,
        'time'    => 3,
        'threads' => 4,
        'label'   => 'M32768-T3-P4',
        'note'    => 'Memori rendah',
    ],
    [
        'id'      => 'S3',
        'group'   => 'A',
        'memory'  => 65536,
        'time'    => 3,
        'threads' => 4,
        'label'   => 'M65536-T3-P4',
        'note'    => 'RFC 9106 Second Recommended',
    ],
    [
        'id'      => 'S4',
        'group'   => 'A',
        'memory'  => 131072,
        'time'    => 3,
        'threads' => 4,
        'label'   => 'M131072-T3-P4',
        'note'    => 'Memori menengah',
    ],
    [
        'id'      => 'S5',
        'group'   => 'A',
        'memory'  => 262144,
        'time'    => 3,
        'threads' => 4,
        'label'   => 'M262144-T3-P4',
        'note'    => 'Memori tinggi (berisiko OOM pada 4GB RAM)',
    ],

    // =========================================================================
    // Group B: Variasi Time Cost (fix memory=65536, threads=4)
    // =========================================================================
    [
        'id'      => 'S6',
        'group'   => 'B',
        'memory'  => 65536,
        'time'    => 1,
        'threads' => 4,
        'label'   => 'M65536-T1-P4',
        'note'    => 'Minimum time cost',
    ],
    [
        'id'      => 'S7',
        'group'   => 'B',
        'memory'  => 65536,
        'time'    => 2,
        'threads' => 4,
        'label'   => 'M65536-T2-P4',
        'note'    => 'Time cost rendah',
    ],
    [
        'id'      => 'S8',
        'group'   => 'B',
        'memory'  => 65536,
        'time'    => 3,
        'threads' => 4,
        'label'   => 'M65536-T3-P4',
        'note'    => 'RFC 9106 Second Recommended',
    ],
    [
        'id'      => 'S9',
        'group'   => 'B',
        'memory'  => 65536,
        'time'    => 4,
        'threads' => 4,
        'label'   => 'M65536-T4-P4',
        'note'    => 'Time cost tinggi',
    ],
    [
        'id'      => 'S10',
        'group'   => 'B',
        'memory'  => 65536,
        'time'    => 5,
        'threads' => 4,
        'label'   => 'M65536-T5-P4',
        'note'    => 'Time cost sangat tinggi',
    ],

    // =========================================================================
    // Group C: Variasi Parallelism (fix memory=65536, time=3)
    // =========================================================================
    [
        'id'      => 'S11',
        'group'   => 'C',
        'memory'  => 65536,
        'time'    => 3,
        'threads' => 1,
        'label'   => 'M65536-T3-P1',
        'note'    => 'Single-threaded baseline',
    ],
    [
        'id'      => 'S12',
        'group'   => 'C',
        'memory'  => 65536,
        'time'    => 3,
        'threads' => 2,
        'label'   => 'M65536-T3-P2',
        'note'    => 'Parallelism rendah',
    ],
    [
        'id'      => 'S13',
        'group'   => 'C',
        'memory'  => 65536,
        'time'    => 3,
        'threads' => 4,
        'label'   => 'M65536-T3-P4',
        'note'    => 'RFC 9106 Second Recommended',
    ],
    [
        'id'      => 'S14',
        'group'   => 'C',
        'memory'  => 65536,
        'time'    => 3,
        'threads' => 8,
        'label'   => 'M65536-T3-P8',
        'note'    => 'Over-subscription (melebihi core fisik)',
    ],

    // =========================================================================
    // Group D: Kepatuhan RFC 9106
    // =========================================================================
    [
        'id'      => 'S15',
        'group'   => 'D',
        'memory'  => 2097152,
        'time'    => 1,
        'threads' => 4,
        'label'   => 'RFC9106-First',
        'note'    => 'RFC 9106 First Recommended (server berdaya tinggi)',
    ],
    [
        'id'      => 'S16',
        'group'   => 'D',
        'memory'  => 65536,
        'time'    => 3,
        'threads' => 4,
        'label'   => 'RFC9106-Second',
        'note'    => 'RFC 9106 Second Recommended (perangkat terbatas)',
    ],
];

// =============================================================================
// PARSING ARGUMEN CLI
// =============================================================================

/**
 * Mengurai argumen CLI dengan format --key=value
 *
 * @param  array $argv Argumen dari command line
 * @return array Daftar parameter yang telah diurai
 */
function parseArgs(array $argv): array
{
    $args = [];
    for ($i = 1; $i < count($argv); $i++) {
        if (str_starts_with($argv[$i], '--')) {
            $pair = substr($argv[$i], 2);
            if (str_contains($pair, '=')) {
                [$key, $value] = explode('=', $pair, 2);
                $args[$key] = $value;
            } else {
                $args[$pair] = true;
            }
        }
    }
    return $args;
}

$args = parseArgs($argv);

// Parameter default
$iterations   = isset($args['iterations']) ? (int) $args['iterations'] : 10;
$scenarioFilter = isset($args['scenario']) ? (int) $args['scenario'] : null;
$groupFilter  = isset($args['group']) ? strtoupper($args['group']) : null;

// =============================================================================
// FUNGSI UTILITAS
// =============================================================================

/**
 * Mendapatkan path absolut ke script mikro_benchmark.php
 *
 * @return string Path absolut ke mikro_benchmark.php
 */
function getBenchmarkScriptPath(): string
{
    // Gunakan __DIR__ karena script ini berada di direktori benchmark yang sama
    return __DIR__ . '/mikro_benchmark.php';
}

/**
 * Menjalankan satu skenario benchmark via mikro_benchmark.php
 *
 * @param  array  $scenario    Definisi skenario
 * @param  int    $iterations  Jumlah iterasi pengukuran
 * @return array  Hasil benchmark atau array error
 */
function runScenario(array $scenario, int $iterations): array
{
    $scriptPath = getBenchmarkScriptPath();

    // Pastikan script benchmark ada
    if (!file_exists($scriptPath)) {
        return [
            'error'   => true,
            'message' => "Script benchmark tidak ditemukan: {$scriptPath}",
        ];
    }

    // Susun perintah PHP CLI
    $phpBinary  = PHP_BINARY;
    $password   = 'BenchmarkPassword123!';
    $command    = sprintf(
        '%s "%s" --memory=%d --time=%d --threads=%d --iterations=%d --password=%s 2>&1',
        escapeshellcmd($phpBinary),
        escapeshellarg($scriptPath),
        $scenario['memory'],
        $scenario['time'],
        $scenario['threads'],
        $iterations,
        escapeshellarg($password)
    );

    // Jalankan perintah dan tangkap output
    $output     = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);

    // Gabungkan output menjadi string
    $outputStr = implode(PHP_EOL, $output);

    // Periksa apakah eksekusi berhasil
    if ($returnCode !== 0) {
        return [
            'error'      => true,
            'message'    => "Eksekusi gagal (exit code: {$returnCode})",
            'command'    => $command,
            'output'     => $outputStr,
            'return_code' => $returnCode,
        ];
    }

    // Coba decode JSON dari output
    $jsonData = json_decode($outputStr, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error'      => true,
            'message'    => 'Gagal parsing JSON output: ' . json_last_error_msg(),
            'command'    => $command,
            'raw_output' => $outputStr,
        ];
    }

    // Lampirkan metadata skenario ke hasil
    $jsonData['scenario_id'] = $scenario['id'];
    $jsonData['group']       = $scenario['group'];
    $jsonData['label']       = $scenario['label'];
    $jsonData['note']        = $scenario['note'];

    return $jsonData;
}

/**
 * Menampilkan progress bar di konsol
 *
 * @param int $current Posisi saat ini
 * @param int $total   Total skenario
 * @param string $label Label skenario yang sedang berjalan
 */
function displayProgress(int $current, int $total, string $label): void
{
    $width    = 40;
    $ratio    = $current / $total;
    $filled   = (int) round($width * $ratio);
    $empty    = $width - $filled;

    $bar      = str_repeat('█', $filled) . str_repeat('░', $empty);
    $percent  = (int) round($ratio * 100);

    // Gunakan \r untuk menimpa baris yang sama
    fprintf(STDERR, "\r  [%s] %3d%% (%d/%d) %-30s", $bar, $percent, $current, $total, $label);
    if ($current === $total) {
        fprintf(STDERR, PHP_EOL);
    }
}

/**
 * Menampilkan tabel ringkasan perbandingan di konsol
 *
 * @param array $results Hasil semua skenario yang berhasil
 */
function displaySummaryTable(array $results): void
{
    if (empty($results)) {
        fprintf(STDERR, PHP_EOL . "  Tidak ada hasil untuk ditampilkan." . PHP_EOL);
        return;
    }

    fprintf(STDOUT, PHP_EOL);
    fprintf(STDOUT, "  ┌──────┬───────┬────────────┬──────┬─────────┬──────────────┬──────────────┬──────────────┬──────────────┐" . PHP_EOL);
    fprintf(STDOUT, "  │ ID   │ Group │ Mem (MiB)  │ Time │ Threads │ Hash Mean ms │ Hash Med ms  │ Vrfy Mean ms │ Vrfy Med ms  │" . PHP_EOL);
    fprintf(STDOUT, "  ├──────┼───────┼────────────┼──────┼─────────┼──────────────┼──────────────┼──────────────┼──────────────┤" . PHP_EOL);

    foreach ($results as $r) {
        $id       = str_pad($r['scenario_id'] ?? '??', 4);
        $group    = str_pad($r['group'] ?? '?', 5);
        $memMiB   = str_pad(round($r['parameters']['memory_cost_kib'] / 1024, 1), 10);
        $time     = str_pad($r['parameters']['time_cost'], 4);
        $threads  = str_pad($r['parameters']['parallelism'], 7);
        $hashMean = str_pad($r['hash_results']['stats']['mean_ms'] ?? 'ERR', 12);
        $hashMed  = str_pad($r['hash_results']['stats']['median_ms'] ?? 'ERR', 12);
        $vrfyMean = str_pad($r['verify_results']['stats']['mean_ms'] ?? 'ERR', 12);
        $vrfyMed  = str_pad($r['verify_results']['stats']['median_ms'] ?? 'ERR', 12);

        fprintf(STDOUT, "  │ %s │ %s │ %s │ %s │ %s │ %s │ %s │ %s │ %s │" . PHP_EOL,
            $id, $group, $memMiB, $time, $threads, $hashMean, $hashMed, $vrfyMean, $vrfyMed
        );
    }

    fprintf(STDOUT, "  └──────┴───────┴────────────┴──────┴─────────┴──────────────┴──────────────┴──────────────┴──────────────┘" . PHP_EOL);
    fprintf(STDOUT, PHP_EOL);
}

/**
 * Menyimpan hasil ke file JSON
 *
 * @param string $filePath Path file output
 * @param array  $data     Data yang akan disimpan
 * @return bool  Keberhasilan operasi
 */
function saveJson(string $filePath, array $data): bool
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        fprintf(STDERR, "  Error: Gagal encode JSON: %s" . PHP_EOL, json_last_error_msg());
        return false;
    }
    return file_put_contents($filePath, $json) !== false;
}

/**
 * Menyimpan hasil ke file CSV
 *
 * @param string $filePath Path file output
 * @param array  $results  Array hasil benchmark
 * @return bool  Keberhasilan operasi
 */
function saveCsv(string $filePath, array $results): bool
{
    // Header CSV sesuai format yang diminta
    $header = [
        'scenario_id',
        'group',
        'memory_kib',
        'time_cost',
        'threads',
        'hash_mean_ms',
        'hash_median_ms',
        'verify_mean_ms',
        'verify_median_ms',
    ];

    $rows = [];
    $rows[] = $header;

    foreach ($results as $r) {
        $row = [
            $r['scenario_id']             ?? '',
            $r['group']                   ?? '',
            $r['parameters']['memory_cost_kib'] ?? '',
            $r['parameters']['time_cost']      ?? '',
            $r['parameters']['parallelism']    ?? '',
            $r['hash_results']['stats']['mean_ms']   ?? '',
            $r['hash_results']['stats']['median_ms'] ?? '',
            $r['verify_results']['stats']['mean_ms']   ?? '',
            $r['verify_results']['stats']['median_ms'] ?? '',
        ];
        $rows[] = $row;
    }

    // Tulis CSV
    $handle = fopen($filePath, 'w');
    if ($handle === false) {
        fprintf(STDERR, "  Error: Tidak dapat membuat file CSV: %s" . PHP_EOL, $filePath);
        return false;
    }

    // Set BOM untuk kompatibilitas Excel
    fwrite($handle, "\xEF\xBB\xBF");

    foreach ($rows as $row) {
        // Escape field yang mengandung koma atau kutip
        $escaped = array_map(function ($field) {
            $str = (string) $field;
            if (str_contains($str, ',') || str_contains($str, '"') || str_contains($str, "\n")) {
                return '"' . str_replace('"', '""', $str) . '"';
            }
            return $str;
        }, $row);
        fputcsv($handle, $escaped);
    }

    fclose($handle);
    return true;
}

// =============================================================================
// FILTER SKENARIO
// =============================================================================

/**
 * Memfilter skenario berdasarkan argumen --scenario dan --group
 *
 * @param  array $allScenarios Semua definisi skenario
 * @param  int|null $scenarioFilter Nomor skenario (1-16)
 * @param  string|null $groupFilter Huruf grup (A-D)
 * @return array Daftar skenario yang lolos filter
 */
function filterScenarios(array $allScenarios, ?int $scenarioFilter, ?string $groupFilter): array
{
    $filtered = $allScenarios;

    // Filter berdasarkan nomor skenario (S1-S16)
    if ($scenarioFilter !== null) {
        if ($scenarioFilter < 1 || $scenarioFilter > count($allScenarios)) {
            fprintf(STDERR, "  Error: Skenario S%d tidak valid. Pilih 1–%d." . PHP_EOL, $scenarioFilter, count($allScenarios));
            exit(1);
        }
        $filtered = array_values(array_filter($filtered, function ($s) use ($scenarioFilter) {
            return (int) substr($s['id'], 1) === $scenarioFilter;
        }));
    }

    // Filter berdasarkan grup
    if ($groupFilter !== null) {
        if (!in_array($groupFilter, ['A', 'B', 'C', 'D'], true)) {
            fprintf(STDERR, "  Error: Grup '%s' tidak valid. Pilih A, B, C, atau D." . PHP_EOL, $groupFilter);
            exit(1);
        }
        $filtered = array_values(array_filter($filtered, function ($s) use ($groupFilter) {
            return $s['group'] === $groupFilter;
        }));
    }

    return $filtered;
}

// =============================================================================
// PROGRAM UTAMA
// =============================================================================

// Judul
fprintf(STDOUT, PHP_EOL);
fprintf(STDOUT, "  ╔══════════════════════════════════════════════════════════════╗" . PHP_EOL);
fprintf(STDOUT, "  ║  BATCH RUNNER: Benchmark Argon2id — Semua Skenario         ║" . PHP_EOL);
fprintf(STDOUT, "  ╚══════════════════════════════════════════════════════════════╝" . PHP_EOL);
fprintf(STDOUT, PHP_EOL);

// Tampilkan info konfigurasi
fprintf(STDOUT, "  PHP Version   : %s" . PHP_EOL, PHP_VERSION);
fprintf(STDOUT, "  Iterasi       : %d per skenario" . PHP_EOL, $iterations);
fprintf(STDOUT, "  Password      : BenchmarkPassword123! (21 karakter)" . PHP_EOL);

// Tampilkan filter yang aktif
if ($scenarioFilter !== null) {
    fprintf(STDOUT, "  Filter        : Skenario S%d saja" . PHP_EOL, $scenarioFilter);
} elseif ($groupFilter !== null) {
    fprintf(STDOUT, "  Filter        : Grup %s saja" . PHP_EOL, $groupFilter);
} else {
    fprintf(STDOUT, "  Filter        : Semua skenario (16)" . PHP_EOL);
}

fprintf(STDOUT, PHP_EOL);

// Filter skenario
$scenariosToRun = filterScenarios($SCENARIOS, $scenarioFilter, $groupFilter);
$total = count($scenariosToRun);

if ($total === 0) {
    fprintf(STDERR, "  Tidak ada skenario yang cocok dengan filter." . PHP_EOL);
    exit(1);
}

fprintf(STDOUT, "  Total skenario: %d" . PHP_EOL, $total);
fprintf(STDOUT, "  ─────────────────────────────────────────────────────────────" . PHP_EOL);
fprintf(STDOUT, PHP_EOL);

// =============================================================================
// EKSEKUSI SEMUA SKENARIO
// =============================================================================

$allResults   = [];
$successCount = 0;
$failCount    = 0;
$startTime    = microtime(true);

foreach ($scenariosToRun as $index => $scenario) {
    $num = $index + 1;

    // Tampilkan progress
    fprintf(STDOUT, "  [%d/%d] Menjalankan %s (%s)...", $num, $total, $scenario['id'], $scenario['label']);
    fprintf(STDOUT, PHP_EOL);

    // Tampilkan progress bar
    displayProgress($num, $total, $scenario['id'] . ' ' . $scenario['label']);

    // Jalankan benchmark
    $result = runScenario($scenario, $iterations);

    // Periksa hasil
    if (isset($result['error']) && $result['error'] === true) {
        $failCount++;
        fprintf(STDOUT, "        ⚠ GAGAL: %s" . PHP_EOL, $result['message']);
        if (isset($result['output'])) {
            fprintf(STDOUT, "        Output: %s" . PHP_EOL, substr($result['output'], 0, 200));
        }
        // Simpan record error dalam hasil
        $allResults[] = [
            'scenario_id'      => $scenario['id'],
            'group'            => $scenario['group'],
            'label'            => $scenario['label'],
            'note'             => $scenario['note'],
            'status'           => 'error',
            'error_message'    => $result['message'] ?? 'Unknown error',
            'parameters'       => [
                'memory_cost_kib' => $scenario['memory'],
                'memory_cost_mib' => round($scenario['memory'] / 1024, 1),
                'time_cost'       => $scenario['time'],
                'parallelism'     => $scenario['threads'],
            ],
            'timestamp'        => date('c'),
        ];
    } else {
        $successCount++;
        $result['status'] = 'success';
        $allResults[] = $result;

        // Tampilkan ringkasan singkat
        $hashMean = $result['hash_results']['stats']['mean_ms'] ?? 'N/A';
        $verifyMean = $result['verify_results']['stats']['mean_ms'] ?? 'N/A';
        fprintf(STDOUT, "        ✓ Hash: %s ms | Verify: %s ms" . PHP_EOL, $hashMean, $verifyMean);
    }

    fprintf(STDOUT, PHP_EOL);
}

$endTime = microtime(true);
$totalDuration = round($endTime - $startTime, 2);

// =============================================================================
// SIMPAN HASIL KE FILE
// =============================================================================

fprintf(STDOUT, "  ══════════════════════════════════════════════════════════════" . PHP_EOL);
fprintf(STDOUT, "  Menyimpan hasil..." . PHP_EOL);
fprintf(STDOUT, PHP_EOL);

// Buat direktori results jika belum ada
$resultsDir = __DIR__ . '/results';
if (!is_dir($resultsDir)) {
    mkdir($resultsDir, 0755, true);
    fprintf(STDOUT, "  ✓ Direktori results/ telah dibuat" . PHP_EOL);
}

// Susun data output
$outputData = [
    'metadata' => [
        'generated_at'     => date('c'),
        'php_version'      => PHP_VERSION,
        'iterations'       => $iterations,
        'total_scenarios'  => $total,
        'success_count'    => $successCount,
        'fail_count'       => $failCount,
        'total_duration_s' => $totalDuration,
        'filter'           => [
            'scenario' => $scenarioFilter,
            'group'    => $groupFilter,
        ],
    ],
    'scenarios' => $allResults,
];

// Simpan JSON
$jsonPath = $resultsDir . '/all_scenarios.json';
if (saveJson($jsonPath, $outputData)) {
    fprintf(STDOUT, "  ✓ JSON tersimpan: %s" . PHP_EOL, $jsonPath);
} else {
    fprintf(STDERR, "  ✗ Gagal menyimpan JSON" . PHP_EOL);
}

// Simpan CSV (hanya skenario yang berhasil)
$successfulResults = array_filter($allResults, function ($r) {
    return ($r['status'] ?? '') === 'success';
});
$successfulResults = array_values($successfulResults);

$csvPath = $resultsDir . '/summary.csv';
if (saveCsv($csvPath, $successfulResults)) {
    fprintf(STDOUT, "  ✓ CSV tersimpan:  %s" . PHP_EOL, $csvPath);
} else {
    fprintf(STDERR, "  ✗ Gagal menyimpan CSV" . PHP_EOL);
}

// =============================================================================
// TAMPILKAN TABEL RINGKASAN
// =============================================================================

fprintf(STDOUT, PHP_EOL);
fprintf(STDOUT, "  ══════════════════════════════════════════════════════════════" . PHP_EOL);
fprintf(STDOUT, "  RINGKASAN HASIL BENCHMARK" . PHP_EOL);
fprintf(STDOUT, "  ══════════════════════════════════════════════════════════════" . PHP_EOL);

displaySummaryTable($successfulResults);

// Tampilkan statistik akhir
fprintf(STDOUT, "  Statistik Eksekusi:" . PHP_EOL);
fprintf(STDOUT, "    Total skenario : %d" . PHP_EOL, $total);
fprintf(STDOUT, "    Berhasil       : %d" . PHP_EOL, $successCount);
fprintf(STDOUT, "    Gagal          : %d" . PHP_EOL, $failCount);
fprintf(STDOUT, "    Durasi total   : %.2f detik" . PHP_EOL, $totalDuration);
fprintf(STDOUT, PHP_EOL);

// Tandai skenario yang melebihi threshold
fprintf(STDOUT, "  Evaluasi Threshold Keamanan (50-250ms = optimal):" . PHP_EOL);
foreach ($successfulResults as $r) {
    $hashMean = $r['hash_results']['stats']['mean_ms'] ?? 0;
    $status   = '';

    if ($hashMean < 50) {
        $status = 'Terlalu cepat (kurang aman)';
    } elseif ($hashMean <= 250) {
        $status = 'OPTIMAL';
    } elseif ($hashMean <= 500) {
        $status = 'Dapat diterima';
    } elseif ($hashMean <= 1000) {
        $status = 'Ambang batas maksimum';
    } else {
        $status = 'Tidak dapat diterima (>1 detik)';
    }

    fprintf(STDOUT, "    %s (%s): %8.2f ms — %s" . PHP_EOL,
        $r['scenario_id'], $r['label'], $hashMean, $status
    );
}
fprintf(STDOUT, PHP_EOL);
fprintf(STDOUT, "  ══════════════════════════════════════════════════════════════" . PHP_EOL);
fprintf(STDOUT, "  Selesai." . PHP_EOL);
fprintf(STDOUT, PHP_EOL);

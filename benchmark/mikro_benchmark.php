<?php

/**
 * =============================================================================
 * MIKRO-LAYER BENCHMARK: Argon2id Password Hashing (Pure PHP)
 * =============================================================================
 *
 * Script benchmark mandiri untuk pengukuran performa hashing Argon2id
 * pada level mikro (tanpa overhead framework Laravel).
 *
 * Menggunakan fungsi native PHP: password_hash() dan password_verify().
 *
 * Penggunaan:
 *   php benchmark/mikro_benchmark.php --memory=65536 --time=3 --threads=4 --iterations=10
 *
 * Parameter RFC 9106 yang direkomendasikan:
 *   - memory_cost: 65536 KiB (64 MiB)
 *   - time_cost:   3 iterasi
 *   - parallelism: 4 thread
 *
 * @author  Skripsi Argon2id Auth
 * @version 1.0
 * @date    2026-05-22
 */

// =============================================================================
// PENGECEKAN DUKUNGAN ARGON2ID
// =============================================================================

if (!defined('PASSWORD_ARGON2ID')) {
    fwrite(STDERR, json_encode([
        'error' => 'PHP tidak mendukung PASSWORD_ARGON2ID',
        'message' => 'Diperlukan PHP >= 7.3 dengan ext-sodium terinstall',
        'php_version' => PHP_VERSION,
    ]) . PHP_EOL);
    exit(1);
}

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

// Parameter dengan nilai default (RFC 9106 rekomendasi kedua)
$memoryCost = isset($args['memory']) ? (int) $args['memory'] : 65536;
$timeCost   = isset($args['time'])   ? (int) $args['time']   : 3;
$threads    = isset($args['threads']) ? (int) $args['threads'] : 4;
$iterations = isset($args['iterations']) ? (int) $args['iterations'] : 10;
$password   = isset($args['password']) ? $args['password'] : 'passwordBenchmark123!';

// =============================================================================
// FUNGSI UTILITAS STATISTIK
// =============================================================================

/**
 * Menghitung nilai tengah (median) dari array angka yang telah diurutkan
 *
 * @param  array $sorted Array angka yang sudah terurut
 * @return float Nilai median
 */
function median(array $sorted): float
{
    $count = count($sorted);
    $middle = intdiv($count, 2);
    if ($count % 2 === 0) {
        return ($sorted[$middle - 1] + $sorted[$middle]) / 2.0;
    }
    return (float) $sorted[$middle];
}

/**
 * Menghitung simpangan baku (standar deviasi) dari data
 *
 * @param  array $values Array nilai data
 * @param  float $mean   Nilai rata-rata
 * @return float Simpangan baku
 */
function stdDev(array $values, float $mean): float
{
    $sumSquaredDiff = 0.0;
    foreach ($values as $v) {
        $diff = $v - $mean;
        $sumSquaredDiff += $diff * $diff;
    }
    $variance = $sumSquaredDiff / count($values);
    return sqrt($variance);
}

/**
 * Menghitung statistik lengkap dari array timing
 *
 * @param  array $times Array waktu dalam milidetik
 * @return array Statistik: mean, median, min, max, std_dev (semua dalam ms)
 */
function computeStats(array $times): array
{
    $sorted = $times;
    sort($sorted, SORT_NUMERIC);

    $count = count($sorted);
    $sum = array_sum($sorted);
    $mean = $sum / $count;
    $med = median($sorted);
    $min = $sorted[0];
    $max = $sorted[$count - 1];
    $std = stdDev($sorted, $mean);

    return [
        'mean_ms'    => round($mean, 2),
        'median_ms'  => round($med, 2),
        'min_ms'     => round($min, 2),
        'max_ms'     => round($max, 2),
        'std_dev_ms' => round($std, 2),
    ];
}

// =============================================================================
// PROSES BENCHMARK
// =============================================================================

// Konstruksi opsi hashing Argon2id sesuai RFC 9106
$hashOptions = [
    'memory_cost' => $memoryCost,
    'time_cost'   => $timeCost,
    'threads'     => $threads,
];

// --- Fase pemanasan (warm-up) ---
// Iterasi pertama tidak dihitung, bertujuan agar CPU cache dan memory
// dalam keadaan stabil sebelum pengukuran sesungguhnya.
$warmupHash = password_hash($password, PASSWORD_ARGON2ID, $hashOptions);
password_verify($password, $warmupHash);

// --- Fase pengukuran hashing ---
$hashTimes = [];
for ($i = 0; $i < $iterations; $i++) {
    $start = microtime(true);
    $hash = password_hash($password, PASSWORD_ARGON2ID, $hashOptions);
    $end = microtime(true);

    $elapsedMs = ($end - $start) * 1000.0;
    $hashTimes[] = round($elapsedMs, 3);

    // Simpan hash terakhir untuk fase verifikasi
    $lastHash = $hash;
}

// --- Fase pengukuran verifikasi ---
$verifyTimes = [];
for ($i = 0; $i < $iterations; $i++) {
    $start = microtime(true);
    $result = password_verify($password, $lastHash);
    $end = microtime(true);

    $elapsedMs = ($end - $start) * 1000.0;
    $verifyTimes[] = round($elapsedMs, 3);
}

// =============================================================================
// SUSUN OUTPUT JSON
// =============================================================================

$output = [
    'layer'               => 'mikro',
    'php_version'         => PHP_VERSION,
    'argon2id_supported'  => true,
    'parameters'          => [
        'memory_cost_kib' => $memoryCost,
        'memory_cost_mib' => round($memoryCost / 1024, 1),
        'time_cost'       => $timeCost,
        'parallelism'     => $threads,
    ],
    'iterations'          => $iterations,
    'warmup'              => true,
    'password_length'     => strlen($password),
    'hash_results'        => [
        'times_ms' => $hashTimes,
        'stats'    => computeStats($hashTimes),
    ],
    'verify_results'      => [
        'times_ms' => $verifyTimes,
        'stats'    => computeStats($verifyTimes),
    ],
];

// Cetak JSON ke stdout
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

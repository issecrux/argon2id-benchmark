<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BenchmarkController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'argon2id_supported' => defined('PASSWORD_ARGON2ID'),
            'php_version' => PHP_VERSION,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Macro benchmark: full login flow dengan isolated timing.
     * Memisahkan waktu Argon2id dari overhead framework dan DB query.
     * Sesuai rekomendasi NotebookLM: dual-layer measurement approach.
     */
    public function fullAuth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $requestStart = hrtime(true);

        // DB query timing terpisah
        $dbStart = hrtime(true);
        $user = User::where('email', $validated['email'])->first();
        $dbTime = (hrtime(true) - $dbStart) / 1e6;

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Isolated Argon2id verification - data utama penelitian
        $verifyStart = hrtime(true);
        $valid = Hash::check($validated['password'], $user->password);
        $verifyTime = (hrtime(true) - $verifyStart) / 1e6;

        $totalTime = (hrtime(true) - $requestStart) / 1e6;

        return response()->json([
            'authenticated' => $valid,
            'timing' => [
                'argon2id_verify_ms' => round($verifyTime, 4),
                'db_query_ms' => round($dbTime, 4),
                'framework_overhead_ms' => round($totalTime - $verifyTime - $dbTime, 4),
                'total_ms' => round($totalTime, 4),
            ],
        ]);
    }

    /**
     * Hash benchmark dengan parameter kustom + statistik deskriptif.
     * Untuk pengujian variasi parameter via API (macro layer).
     */
    public function hashWithParams(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string',
            'memory' => 'required|integer|min:1024',
            'time' => 'required|integer|min:1|max:10',
            'threads' => 'required|integer|min:1|max:16',
            'iterations' => 'integer|min:1|max:50',
        ]);

        $iterations = $validated['iterations'] ?? 10;
        $times = [];

        // Warm-up run (tidak dihitung ke statistik)
        Hash::make($validated['password'], [
            'memory' => $validated['memory'],
            'time' => $validated['time'],
            'threads' => $validated['threads'],
        ]);

        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);
            Hash::make($validated['password'], [
                'memory' => $validated['memory'],
                'time' => $validated['time'],
                'threads' => $validated['threads'],
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

        return response()->json([
            'params' => [
                'memory_cost' => $validated['memory'],
                'time_cost' => $validated['time'],
                'parallelism' => $validated['threads'],
            ],
            'iterations' => $iterations,
            'stats' => [
                'mean_ms' => round($mean, 4),
                'median_ms' => round($median, 4),
                'min_ms' => round(min($times), 4),
                'max_ms' => round(max($times), 4),
                'std_dev_ms' => round(sqrt($variance), 4),
            ],
        ]);
    }
}

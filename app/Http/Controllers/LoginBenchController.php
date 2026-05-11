<?php

namespace App\Http\Controllers;

use App\Support\Bench\BenchTimer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class LoginBenchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'scenario' => ['nullable', 'string'],
        ]);

        $dbMs = 0.0;

        DB::listen(static function ($query) use (&$dbMs): void {
            $dbMs += $query->time;
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $start = BenchTimer::now();
        $authStart = BenchTimer::now();
        $success = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ]);
        $authMs = BenchTimer::millisecondsSince($authStart);

        if ($success) {
            $request->session()->regenerate();
        }

        $loginMs = BenchTimer::millisecondsSince($start);

        return response()->json([
            'success' => $success,
            'scenario' => $credentials['scenario'] ?? null,
            't_login_ms' => $loginMs,
            'prof_auth_ms' => $authMs,
            'prof_db_ms' => round($dbMs, 6),
        ], $success ? 200 : 401);
    }
}

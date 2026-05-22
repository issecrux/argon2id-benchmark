<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:128',
        ]);

        // Isolated hashing time - data penelitian
        $hashStart = hrtime(true);
        $hashed = Hash::make($validated['password']);
        $hashTime = (hrtime(true) - $hashStart) / 1e6;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $hashed,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'hash_time_ms' => round($hashTime, 4),
        ], 201);
    }

    public function login(Request $request): JsonResponse
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
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Isolated Argon2id verification timing - data utama penelitian
        $verifyStart = hrtime(true);
        $valid = Hash::check($validated['password'], $user->password);
        $verifyTime = (hrtime(true) - $verifyStart) / 1e6;

        if (! $valid) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $totalTime = (hrtime(true) - $requestStart) / 1e6;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'timing' => [
                'argon2id_verify_ms' => round($verifyTime, 4),
                'db_query_ms' => round($dbTime, 4),
                'framework_overhead_ms' => round($totalTime - $verifyTime - $dbTime, 4),
                'total_ms' => round($totalTime, 4),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}

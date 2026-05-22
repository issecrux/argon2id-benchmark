<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_stores_single_argon2id_hash(): void
    {
        $password = 'password123';

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $password,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'token', 'hash_time_ms']);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // Verify password is hashed with Argon2id (single hash, not double)
        $this->assertTrue(str_starts_with($user->password, '$argon2id$'));
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_register_returns_hash_time(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'token',
            'hash_time_ms',
        ]);
        $this->assertIsNumeric($response->json('hash_time_ms'));
    }

    public function test_login_returns_timing_data(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'token',
            'timing' => [
                'argon2id_verify_ms',
                'db_query_ms',
                'framework_overhead_ms',
                'total_ms',
            ],
        ]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_login_rejects_nonexistent_user(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password456',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_short_password(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_benchmark_health_endpoint(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'argon2id_supported',
            'php_version',
            'timestamp',
        ]);
        $this->assertEquals('ok', $response->json('status'));
    }

    public function test_benchmark_hash_with_params_returns_stats(): void
    {
        $response = $this->postJson('/api/benchmark/hash', [
            'password' => 'testpassword',
            'memory' => 65536,
            'time' => 3,
            'threads' => 4,
            'iterations' => 3,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'params' => ['memory_cost', 'time_cost', 'parallelism'],
            'iterations',
            'stats' => ['mean_ms', 'median_ms', 'min_ms', 'max_ms', 'std_dev_ms'],
        ]);
    }
}

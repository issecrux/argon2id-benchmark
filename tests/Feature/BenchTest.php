<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Bench\ScenarioRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BenchTest extends TestCase
{
    use RefreshDatabase;

    public function test_s1_login_passes(): void
    {
        $scenario = (new ScenarioRepository)->find('S1');

        $this->assertNotNull($scenario);

        $hash = password_hash('Pa$$w0rd!', PASSWORD_ARGON2ID, $scenario->passwordHashOptions());

        User::query()->create([
            'name' => 'Benchmark User',
            'email' => 'user@example.com',
            'password' => $hash,
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login-bench', [
            'email' => 'user@example.com',
            'password' => 'Pa$$w0rd!',
            'scenario' => 'S1',
        ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'scenario',
                't_login_ms',
                'prof_auth_ms',
                'prof_db_ms',
            ]);
    }
}

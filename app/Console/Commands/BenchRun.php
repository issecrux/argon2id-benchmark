<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Bench\BenchTimer;
use App\Support\Bench\CsvResultWriter;
use App\Support\Bench\Scenario;
use App\Support\Bench\ScenarioRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

final class BenchRun extends Command
{
    protected $signature = 'bench:run
        {--iterations= : Jumlah pengulangan per skenario}
        {--output= : Lokasi file CSV}
        {--include-unsafe : Sertakan skenario reference-only yang melewati batas default}
        {--skip-migrate : Lewati migrate otomatis}';

    protected $description = 'Run Argon2id micro and macro authentication benchmarks.';

    public function handle(ScenarioRepository $scenarios): int
    {
        ini_set('memory_limit', '2048M');

        if (! $this->passesPreflight()) {
            return self::FAILURE;
        }

        if (! $this->option('skip-migrate')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        if (! Schema::hasTable('users')) {
            $this->error('Users table not found. Run php artisan migrate first.');

            return self::FAILURE;
        }

        $iterations = (int) ($this->option('iterations') ?: config('bench.iterations'));
        $warmups = (int) config('bench.warmup_iterations');
        $selectedScenarios = $scenarios->all((bool) $this->option('include-unsafe'));
        $output = $this->option('output') ?: config('bench.results_path');
        $writer = new CsvResultWriter(base_path($output));
        $runId = 1;
        $totalRuns = count($selectedScenarios) * $iterations;

        $this->info("Writing results to {$output}");

        $progress = new ProgressBar($this->output, $totalRuns);
        $progress->start();

        foreach ($selectedScenarios as $scenario) {
            if (! $this->scenarioIsAllowed($scenario)) {
                $progress->finish();
                $this->newLine();
                $this->error("Scenario {$scenario->id} exceeds BENCH_MAX_MEMORY_KIB.");

                return self::FAILURE;
            }

            for ($warmup = 0; $warmup < $warmups; $warmup++) {
                $hash = $this->hashPassword($scenario);
                $this->storeDemoUser($hash);
                $this->postLoginBench($scenario);
            }

            for ($iteration = 1; $iteration <= $iterations; $iteration++) {
                $hashStart = BenchTimer::now();
                $hash = $this->hashPassword($scenario);
                $hashMs = BenchTimer::millisecondsSince($hashStart);
                $this->storeDemoUser($hash);

                $login = $this->postLoginBench($scenario);

                $writer->write([
                    'run_id' => $runId++,
                    'scenario' => $scenario->id,
                    'label' => $scenario->label,
                    'mem_kib' => $scenario->memoryCost,
                    't_cost' => $scenario->timeCost,
                    'threads' => $scenario->threads,
                    'iteration' => $iteration,
                    't_hash_ms' => $hashMs,
                    't_login_ms' => $login['t_login_ms'],
                    'login_success' => $login['success'],
                    'http_status' => $login['http_status'],
                    'prof_auth_ms' => $login['prof_auth_ms'],
                    'prof_db_ms' => $login['prof_db_ms'],
                    'hash_len' => strlen($hash),
                    'measured_at' => now()->toIso8601String(),
                ]);

                $progress->advance();

                if (! $login['success']) {
                    $progress->finish();
                    $this->newLine();
                    $this->error("Login benchmark failed for {$scenario->id} iteration {$iteration}.");

                    return self::FAILURE;
                }
            }
        }

        $progress->finish();
        $this->newLine(2);
        $this->info("Completed {$totalRuns} runs.");
        $this->info("CSV: {$writer->path()}");

        return self::SUCCESS;
    }

    private function passesPreflight(): bool
    {
        if (! in_array(PASSWORD_ARGON2ID, password_algos(), true)) {
            $this->error('Argon2id not supported in current PHP build.');

            return false;
        }

        if (! extension_loaded('pdo_sqlite') || ! extension_loaded('sqlite3')) {
            $this->error('SQLite extensions pdo_sqlite and sqlite3 are required.');

            return false;
        }

        $database = config('database.connections.sqlite.database');

        if (is_string($database) && $database !== ':memory:' && ! File::exists($database)) {
            File::ensureDirectoryExists(dirname($database));
            File::put($database, '');
        }

        File::ensureDirectoryExists(base_path('results'));
        File::ensureDirectoryExists(storage_path('bench'));

        return true;
    }

    private function scenarioIsAllowed(Scenario $scenario): bool
    {
        if ($this->option('include-unsafe')) {
            return true;
        }

        return $scenario->memoryCost <= (int) config('bench.max_memory_kib');
    }

    private function hashPassword(Scenario $scenario): string
    {
        return password_hash(
            config('bench.demo_user.password'),
            PASSWORD_ARGON2ID,
            $scenario->passwordHashOptions(),
        );
    }

    private function storeDemoUser(string $hash): User
    {
        $user = User::query()->firstOrNew([
            'email' => config('bench.demo_user.email'),
        ]);

        $user->forceFill([
            'name' => config('bench.demo_user.name'),
            'password' => $hash,
            'email_verified_at' => now(),
        ])->saveQuietly();

        return $user;
    }

    private function postLoginBench(Scenario $scenario): array
    {
        $request = SymfonyRequest::create('/login-bench', 'POST', [
            'email' => config('bench.demo_user.email'),
            'password' => config('bench.demo_user.password'),
            'scenario' => $scenario->id,
        ]);
        $response = app()->handle($request);
        $content = $response->getContent();
        $json = is_string($content) ? json_decode($content, true) : null;

        if (! is_array($json)) {
            $json = [];
        }

        return [
            'success' => $response->isOk() && (bool) ($json['success'] ?? false),
            'http_status' => $response->getStatusCode(),
            't_login_ms' => isset($json['t_login_ms']) ? (float) $json['t_login_ms'] : null,
            'prof_auth_ms' => isset($json['prof_auth_ms']) ? (float) $json['prof_auth_ms'] : null,
            'prof_db_ms' => isset($json['prof_db_ms']) ? (float) $json['prof_db_ms'] : null,
        ];
    }
}

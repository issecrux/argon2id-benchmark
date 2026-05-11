<?php

namespace App\Support\Bench;

use RuntimeException;

final class CsvResultWriter
{
    /**
     * @var list<string>
     */
    private array $headers = [
        'run_id',
        'scenario',
        'label',
        'mem_kib',
        't_cost',
        'threads',
        'iteration',
        't_hash_ms',
        't_login_ms',
        'login_success',
        'http_status',
        'prof_auth_ms',
        'prof_db_ms',
        'hash_len',
        'measured_at',
    ];

    public function __construct(private readonly string $path)
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Cannot create results directory: {$directory}");
        }

        $handle = fopen($this->path, 'w');

        if ($handle === false) {
            throw new RuntimeException("Cannot open CSV file: {$this->path}");
        }

        fputcsv($handle, $this->headers);
        fclose($handle);
    }

    /**
     * @param  array<string, int|float|string|bool|null>  $row
     */
    public function write(array $row): void
    {
        $handle = fopen($this->path, 'a');

        if ($handle === false) {
            throw new RuntimeException("Cannot open CSV file: {$this->path}");
        }

        fputcsv($handle, array_map(
            static fn (string $header): int|float|string|null => match (true) {
                is_bool($row[$header] ?? null) => $row[$header] ? 1 : 0,
                default => $row[$header] ?? null,
            },
            $this->headers,
        ));

        fclose($handle);
    }

    public function path(): string
    {
        return $this->path;
    }
}

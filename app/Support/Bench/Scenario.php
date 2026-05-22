<?php

namespace App\Support\Bench;

final readonly class Scenario
{
    public function __construct(
        public string $id,
        public int $memoryCost,
        public int $timeCost,
        public int $threads,
        public string $label,
        public string $category,
        public string $note,
        public bool $defaultEnabled = true,
    ) {}

    public function passwordHashOptions(): array
    {
        return [
            'memory_cost' => $this->memoryCost,
            'time_cost' => $this->timeCost,
            'threads' => $this->threads,
        ];
    }

    public function laravelHashOptions(): array
    {
        return [
            'memory' => $this->memoryCost,
            'time' => $this->timeCost,
            'threads' => $this->threads,
        ];
    }
}

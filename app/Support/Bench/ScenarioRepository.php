<?php

namespace App\Support\Bench;

final class ScenarioRepository
{
    public function all(bool $includeUnsafe = false): array
    {
        $scenarios = [
            new Scenario(
                id: 'R1',
                memoryCost: 2097152,
                timeCost: 1,
                threads: 4,
                label: 'RFC-9106 first recommended',
                category: 'reference-only',
                note: 'Tidak dijalankan default karena membutuhkan 2 GiB per hash.',
                defaultEnabled: false,
            ),

            // Group A: Variasi Memory Cost (fix time=3, threads=4)
            new Scenario(
                id: 'S1',
                memoryCost: 16384,
                timeCost: 3,
                threads: 4,
                label: 'Memory 16 MiB',
                category: 'A-memory',
                note: 'Variasi memory cost terendah.',
            ),
            new Scenario(
                id: 'S2',
                memoryCost: 32768,
                timeCost: 3,
                threads: 4,
                label: 'Memory 32 MiB',
                category: 'A-memory',
                note: 'Variasi memory cost rendah-menengah.',
            ),
            new Scenario(
                id: 'S3',
                memoryCost: 65536,
                timeCost: 3,
                threads: 4,
                label: 'RFC-9106 second recommended',
                category: 'A-memory',
                note: 'Konfigurasi RFC 9106 untuk lingkungan terbatas memori.',
            ),
            new Scenario(
                id: 'S4',
                memoryCost: 131072,
                timeCost: 3,
                threads: 4,
                label: 'Memory 128 MiB',
                category: 'A-memory',
                note: 'Variasi memory cost tinggi.',
            ),
            new Scenario(
                id: 'S5',
                memoryCost: 262144,
                timeCost: 3,
                threads: 4,
                label: 'Memory 256 MiB',
                category: 'A-memory',
                note: 'Variasi memory cost tertinggi.',
            ),

            // Group B: Variasi Time Cost (fix memory=65536, threads=4)
            new Scenario(
                id: 'S6',
                memoryCost: 65536,
                timeCost: 1,
                threads: 4,
                label: 'Time cost 1',
                category: 'B-time',
                note: 'Variasi time cost terendah.',
            ),
            new Scenario(
                id: 'S7',
                memoryCost: 65536,
                timeCost: 2,
                threads: 4,
                label: 'Time cost 2',
                category: 'B-time',
                note: 'Variasi time cost rendah.',
            ),
            new Scenario(
                id: 'S8',
                memoryCost: 65536,
                timeCost: 3,
                threads: 4,
                label: 'Time cost 3 (RFC baseline)',
                category: 'B-time',
                note: 'Baseline RFC 9106 second recommended.',
            ),
            new Scenario(
                id: 'S9',
                memoryCost: 65536,
                timeCost: 4,
                threads: 4,
                label: 'Time cost 4',
                category: 'B-time',
                note: 'Variasi time cost tinggi.',
            ),
            new Scenario(
                id: 'S10',
                memoryCost: 65536,
                timeCost: 5,
                threads: 4,
                label: 'Time cost 5',
                category: 'B-time',
                note: 'Variasi time cost tertinggi.',
            ),

            // Group C: Variasi Parallelism (fix memory=65536, time=3)
            new Scenario(
                id: 'S11',
                memoryCost: 65536,
                timeCost: 3,
                threads: 1,
                label: 'Threads 1 (single)',
                category: 'C-parallelism',
                note: 'Single-threaded baseline.',
            ),
            new Scenario(
                id: 'S12',
                memoryCost: 65536,
                timeCost: 3,
                threads: 2,
                label: 'Threads 2 (dual)',
                category: 'C-parallelism',
                note: 'Dual-core.',
            ),
            new Scenario(
                id: 'S13',
                memoryCost: 65536,
                timeCost: 3,
                threads: 4,
                label: 'Threads 4 (quad)',
                category: 'C-parallelism',
                note: 'Quad-core (RFC baseline).',
            ),
            new Scenario(
                id: 'S14',
                memoryCost: 65536,
                timeCost: 3,
                threads: 8,
                label: 'Threads 8 (over-sub)',
                category: 'C-parallelism',
                note: 'Over-subscription, melebihi core fisik.',
            ),

            // Group D: RFC 9106 Compliance
            new Scenario(
                id: 'S15',
                memoryCost: 19456,
                timeCost: 2,
                threads: 1,
                label: 'OWASP-19MiB baseline',
                category: 'D-reference',
                note: 'Konfigurasi minimum Argon2id OWASP.',
            ),
            new Scenario(
                id: 'S16',
                memoryCost: 47104,
                timeCost: 1,
                threads: 1,
                label: 'OWASP-46MiB baseline',
                category: 'D-reference',
                note: 'Alternatif OWASP dengan memori lebih besar.',
            ),
        ];

        if ($includeUnsafe) {
            return $scenarios;
        }

        return array_values(array_filter(
            $scenarios,
            static fn (Scenario $scenario): bool => $scenario->defaultEnabled,
        ));
    }

    public function find(string $id, bool $includeUnsafe = false): ?Scenario
    {
        foreach ($this->all($includeUnsafe) as $scenario) {
            if ($scenario->id === $id) {
                return $scenario;
            }
        }

        return null;
    }
}

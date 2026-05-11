<?php

namespace App\Support\Bench;

final class ScenarioRepository
{
    /**
     * @return list<Scenario>
     */
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
            new Scenario(
                id: 'S1',
                memoryCost: 19456,
                timeCost: 2,
                threads: 1,
                label: 'OWASP-19MiB baseline',
                category: 'baseline OWASP',
                note: 'Konfigurasi minimum Argon2id OWASP.',
            ),
            new Scenario(
                id: 'S2',
                memoryCost: 47104,
                timeCost: 1,
                threads: 1,
                label: 'OWASP-46MiB baseline',
                category: 'baseline OWASP',
                note: 'Alternatif OWASP dengan memori lebih besar dan iterasi lebih rendah.',
            ),
            new Scenario(
                id: 'S3',
                memoryCost: 65536,
                timeCost: 3,
                threads: 4,
                label: 'RFC-9106 second recommended',
                category: 'baseline RFC 9106',
                note: 'Konfigurasi RFC 9106 untuk lingkungan terbatas memori.',
            ),
            new Scenario(
                id: 'S4',
                memoryCost: 9216,
                timeCost: 4,
                threads: 1,
                label: 'OWASP low-memory variant',
                category: 'low setting',
                note: 'Titik low-memory untuk membaca trade-off iterasi lebih tinggi.',
            ),
            new Scenario(
                id: 'S5',
                memoryCost: 131072,
                timeCost: 2,
                threads: 2,
                label: 'Mid-high controlled stress',
                category: 'stress setting',
                note: 'Titik stress menengah di bawah batas memori penelitian.',
            ),
            new Scenario(
                id: 'S6',
                memoryCost: 262144,
                timeCost: 1,
                threads: 2,
                label: 'Stress-memory low-spec boundary',
                category: 'stress setting',
                note: 'Titik stress memori untuk perangkat berspesifikasi rendah.',
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

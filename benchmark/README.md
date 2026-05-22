# Benchmark System — Implementasi dan Analisis Optimasi Parameter Argon2id

## Ringkasan

Sistem benchmark untuk mengukur performa Argon2id pada sistem autentikasi aplikasi web di lingkungan berspesifikasi rendah. Penelitian ini menerapkan pendekatan pengukuran dua lapis (*dual-layer measurement*) untuk memisahkan performa murni algoritma Argon2id dari overhead framework Laravel.

**Lingkungan pengujian:**
- Intel Core i3-10110U (2 core / 4 thread), 4 GB RAM, Windows 10
- PHP 8.x (CLI untuk mikro layer, FPM untuk makro layer)
- Laravel 10.x dengan SQLite

---

## Struktur Direktori

```
benchmark/
├── mikro_benchmark.php      — Skrip benchmark murni PHP (tanpa Laravel)
├── run_all_scenarios.php    — Batch runner untuk semua 16 skenario
├── SCENARIOS.md             — Dokumentasi lengkap 16 skenario pengujian
├── METRICS.md               — Definisi 10 metrik efisiensi autentikasi
├── TEMPLATE_hasil_pengujian.csv — Template pencatatan data mentah
├── TEMPLATE_rangkuman.csv   — Template pencatatan data ringkasan
├── results/                 — Direktori output hasil benchmark (JSON/CSV)
└── README.md                — Dokumen ini
```

---

## Arsitektur Pengukuran Dua Lapis

Penelitian ini memisahkan pengukuran menjadi dua layer untuk mengisolasi kontribusi masing-masing komponen:

```
┌─────────────────────────────────────────────────────┐
│            LAYER MAKRO (Laravel API)                 │
│  ┌───────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │ HTTP/MW   │→ │ DB Query │→ │ Argon2id Verify  │  │
│  │ Overhead  │  │ (SQLite) │  │ (isolated timer) │  │
│  └───────────┘  └──────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│            LAYER MIKRO (PHP CLI)                     │
│  ┌──────────────────────────────────────────────┐    │
│  │  password_hash() / password_verify()          │    │
│  │  Pure Argon2id computation — no overhead      │    │
│  └──────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────┘
```

| Layer | Metode | Tujuan |
|-------|--------|--------|
| **Mikro** | PHP CLI langsung, `password_hash()` native | Mengisolasi waktu komputasi murni Argon2id |
| **Makro** | HTTP request ke Laravel API (`BenchmarkController`/`AuthController`) | Mengukur performa autentikasi penuh dalam konteks aplikasi nyata |

---

## Cara Penggunaan

### Prasyarat

```bash
php -v                                    # PHP >= 8.x
php -m | findstr sodium                   # Ekstensi sodium aktif
php -r "echo PASSWORD_ARGON2ID;"          # Konstanta tersedia
```

### 1. Benchmark Satu Skenario (Mikro Layer)

```bash
php benchmark/mikro_benchmark.php --memory=65536 --time=3 --threads=4 --iterations=10
```

**Parameter tersedia:**

| Parameter | Default | Keterangan |
|-----------|---------|------------|
| `--memory` | 65536 | `memory_cost` dalam KiB |
| `--time` | 3 | `time_cost` (jumlah iterasi pass) |
| `--threads` | 4 | `parallelism` (jumlah thread) |
| `--iterations` | 10 | Jumlah iterasi pengukuran |
| `--password` | `passwordBenchmark123!` | Password input |

### 2. Jalankan Semua Skenario

```bash
php benchmark/run_all_scenarios.php
```

Menjalankan 16 skenario secara berurutan (Group A → B → C → D) dengan output JSON per skenario dan file ringkasan CSV gabungan.

### 3. Jalankan Skenario Tertentu

```bash
php benchmark/run_all_scenarios.php --scenario=3
```

Menjalankan satu skenario berdasarkan ID (S1–S16).

### 4. Benchmark Makro Layer (Laravel API)

```bash
# Benchmark hash dengan parameter kustom
curl -X POST http://localhost:8000/api/benchmark/hash \
  -H "Content-Type: application/json" \
  -d '{"password":"test1234","memory":65536,"time":3,"threads":4,"iterations":10}'

# Benchmark login penuh
curl -X POST http://localhost:8000/api/benchmark/auth \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"test1234"}'
```

---

## Ringkasan 16 Skenario Pengujian

Skenario dikelompokkan dalam 4 grup. Setiap grup menahan dua parameter konstan dan memvariasikan satu parameter:

| ID | Group | memory_cost | time_cost | parallelism | Label |
|----|-------|-------------|-----------|-------------|-------|
| S1 | A — Variasi Memory | 16384 (16 MiB) | 3 | 4 | M16384-T3-P4 |
| S2 | A — Variasi Memory | 32768 (32 MiB) | 3 | 4 | M32768-T3-P4 |
| **S3** | **A** | **65536 (64 MiB)** | **3** | **4** | **M65536-T3-P4 (RFC 9106)** |
| S4 | A — Variasi Memory | 131072 (128 MiB) | 3 | 4 | M131072-T3-P4 |
| S5 | A — Variasi Memory | 262144 (256 MiB) | 3 | 4 | M262144-T3-P4 |
| S6 | B — Variasi Time | 65536 (64 MiB) | 1 | 4 | M65536-T1-P4 |
| S7 | B — Variasi Time | 65536 (64 MiB) | 2 | 4 | M65536-T2-P4 |
| **S8** | **B** | **65536 (64 MiB)** | **3** | **4** | **M65536-T3-P4 (RFC 9106)** |
| S9 | B — Variasi Time | 65536 (64 MiB) | 4 | 4 | M65536-T4-P4 |
| S10 | B — Variasi Time | 65536 (64 MiB) | 5 | 4 | M65536-T5-P4 |
| S11 | C — Variasi Parallelism | 65536 (64 MiB) | 3 | 1 | M65536-T3-P1 |
| S12 | C — Variasi Parallelism | 65536 (64 MiB) | 3 | 2 | M65536-T3-P2 |
| **S13** | **C** | **65536 (64 MiB)** | **3** | **4** | **M65536-T3-P4 (RFC 9106)** |
| S14 | C — Variasi Parallelism | 65536 (64 MiB) | 3 | 8 | M65536-T3-P8 |
| S15 | D — RFC 9106 Compliance | 2097152 (2 GiB) | 1 | 4 | RFC9106-First |
| **S16** | **D** | **65536 (64 MiB)** | **3** | **4** | **RFC9106-Second** |

> S3, S8, S13, dan S16 memiliki parameter identik (65536/3/4) — RFC 9106 Second Recommended.

### Alokasi Memori per Skenario

| Skenario | Alokasi Simultan (memory × threads) | Status pada 4 GB RAM |
|----------|--------------------------------------|----------------------|
| S1 | 64 MiB | Aman |
| S2 | 128 MiB | Aman |
| S3/S8/S13/S16 | 256 MiB | Aman |
| S4 | 512 MiB | Perlu monitoring |
| S5 | 1 GiB | Berisiko OOM |
| S15 | 8 GiB | **Diharapkan gagal / OOM** |

---

## Metrik Pengukuran

Sistem benchmark menghitung 10 metrik efisiensi autentikasi. Lihat [`METRICS.md`](./METRICS.md) untuk definisi lengkap.

| No | Metrik | Formula | Satuan | Threshold |
|----|--------|---------|--------|-----------|
| 1 | **Hashing Time** | `(end - start) × 1000` | ms | 50–250ms (interactive) |
| 2 | **Login Verification Time** | `(end - start) × 1000` | ms | ≤500ms (ideal), ≤1000ms (maks) |
| 3 | **Authentication Efficiency** | `memory_cost_MiB / hash_time_ms` | MiB/ms | Higher = better |
| 4 | **Security-Performance Ratio** | `(memory_cost_MiB × time_cost) / hash_time_ms` | MiB·iterasi/ms | Higher = better |
| 5 | **Framework Overhead Ratio** | `(framework_overhead / total) × 100%` | % | Semakin kecil = lebih baik |
| 6 | **DB Query Time** | `(end - start) × 1000` | ms | — |
| 7 | **Total Authentication Time** | `argon2id + db + framework` | ms | ≤500ms |
| 8 | **DoS Resistance Threshold** | Concurrent requests hingga CPU > 80% | req/s | — |
| 9 | **Memory Efficiency** | `hash_time_ms / memory_cost_MiB` | ms/MiB | Lower = better |
| 10 | **Parallelism Efficiency** | `hash_time(1 thread) / hash_time(N threads)` | ratio | PE > 1 = efektif |

### Ambang Batas Keamanan

```
|<-------- Optimal -------->|<--- Dapat Diterima --->|<---- Maksimum ---->|<- Tidak Diterima ->|
50ms                       250ms                    500ms               1000ms
```

- **< 50 ms:** Terlalu cepat — kurang aman untuk brute-force
- **50–250 ms:** Zona optimal — rekomendasi utama untuk autentikasi web interaktif
- **250–500 ms:** Dapat diterima — sesuai untuk aplikasi non-realtime
- **500–1000 ms:** Hanya untuk skenario keamanan sangat tinggi
- **> 1000 ms:** Tidak dapat diterima — pertimbangkan hashing asinkron

---

## Metodologi Pengukuran

### Persiapan

1. Tutup semua aplikasi yang tidak diperlukan
2. Nonaktifkan Windows Update dan Windows Defender real-time scanning
3. Colokkan charger laptop, atur mode *High Performance*
4. Restart komputer dan tunggu 5 menit agar stabil
5. Verifikasi environment (PHP versi, ekstensi sodium, konstanta `PASSWORD_ARGON2ID`)

### Prosedur per Skenario

| Aspek | Nilai |
|-------|-------|
| **Warm-up** | 1 iterasi per skenario (tidak dihitung) |
| **Iterasi** | 10 iterasi per skenario |
| **Metrik utama** | Rata-rata aritmatika (mean) dari 10 iterasi |
| **Timer** | `microtime(true)` — resolusi mikrodetik |
| **Password test** | `BenchmarkPassword123!` (konsisten di semua skenario) |
| **Jeda antar grup** | 10 menit (pendinginan CPU) |

### Pengendalian Variabel

- Password input selalu sama: `BenchmarkPassword123!` (21 karakter, kompleks)
- Satu sesi per grup tanpa restart untuk menjaga konsistensi suhu CPU
- Tidak ada proses latar belakang selama pengukuran
- Catat suhu CPU pada awal dan akhir setiap skenario

---

## Format Output

### Output Layer Mikro (JSON)

```json
{
    "layer": "mikro",
    "php_version": "8.2.15",
    "parameters": {
        "memory_cost_kib": 65536,
        "memory_cost_mib": 64.0,
        "time_cost": 3,
        "parallelism": 4
    },
    "iterations": 10,
    "hash_results": {
        "stats": {
            "mean_ms": 150.23,
            "median_ms": 149.88,
            "min_ms": 148.23,
            "max_ms": 153.46,
            "std_dev_ms": 1.57
        }
    },
    "verify_results": {
        "stats": {
            "mean_ms": 149.88,
            "median_ms": 149.57,
            "min_ms": 147.89,
            "max_ms": 152.68,
            "std_dev_ms": 1.43
        }
    }
}
```

### Output Layer Makro (JSON)

Layer makro memecah waktu total autentikasi menjadi empat komponen:

- **`argon2id_verify_ms`** — waktu verifikasi hash Argon2id
- **`db_query_ms`** — waktu eksekusi query database
- **`framework_overhead_ms`** — waktu overhead Laravel (routing, middleware, serialization)
- **`total_ms`** — waktu total request hingga response

### Format Export CSV

```
id,label,memory_cost,time_cost,parallelism,mean_ms,median_ms,min_ms,max_ms,std_dev_ms,layer,timestamp
S3,M65536-T3-P4,65536,3,4,150.23,149.88,148.23,153.46,1.57,micro,2026-05-22T18:00:00+07:00
```

---

## Referensi

1. **RFC 9106** — Argon2 Memory-Hard Hash Function (2021). Parameter recommended: First (server ≥ 8 GiB RAM) dan Second (resource-constrained ≤ 4 GiB RAM).
2. **Ntantogian dkk. (2019)** — *Password Hashing: A Study on Performance and Security*. Threshold maksimum 1000ms untuk autentikasi web.
3. **CipherTools (2025)** — *Interactive Login Threshold Guidelines*. Rekomendasi 50–250ms untuk UX optimal.
4. **UtilityKit (2026)** — *Web Application Performance Standards*. Threshold 500ms untuk autentikasi interaktif.
5. **OWASP** — *Password Storage Cheat Sheet*. Rekomendasi parameter hashing untuk aplikasi web.
6. **Biryukov, Dinu, Khovratovich (2016)** — *Argon2: Memory-Hard Hash Functions*. Paper asli pemenang PHC.

---

**Dokumen ini merupakan bagian dari penelitian skripsi: "Implementasi dan Analisis Optimasi Parameter Argon2id pada Sistem Autentikasi Aplikasi Web"**

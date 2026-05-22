# Skenario Benchmark Argon2id

**Judul Skripsi:** Implementasi dan Analisis Optimasi Parameter Argon2id pada Sistem Autentikasi Aplikasi Web

**Dokumen:** Definisi Skenario Benchmark dan Prosedur Pengukuran

---

## 1. Lingkungan Hardware

Seluruh pengujian dilakukan pada perangkat terbatas (*constrained environment*) tanpa menggunakan VPS atau cloud server. Pemilihan hardware ini merepresentasikan skenario deployment pada server kelas menengah ke bawah yang umum digunakan oleh UKM, startup early-stage, atau instansi pendidikan di Indonesia.

| Komponen | Spesifikasi |
|---|---|
| Perangkat | Laptop Lenovo 81WA |
| Prosesor | Intel Core i3-10110U (2 core fisik, 4 thread via Hyper-Threading) |
| Kecepatan Base/Turbo | 2.1 GHz / 4.1 GHz |
| Total RAM | 4096 MB (4 GB) DDR4 |
| Sistem Operasi | Windows 10 Home Single Language 64-bit |
| PHP Version | PHP 8.x dengan ekstensi `sodium` dan dukungan `PASSWORD_ARGON2ID` |

**Pertimbangan pemilihan hardware:**

- Intel i3-10110U memiliki **2 core fisik** yang menjadi batas utama efektivitas parameter `threads`. Hyper-Threading menghasilkan 4 thread logis, namun tidak memberikan performa parallelisme sebenarnya untuk komputasi CPU-bound seperti Argon2id.
- RAM 4 GB menjadi kendala utama untuk parameter `memory_cost` yang tinggi, karena Argon2id mengalokasikan memori sebesar `memory_cost × parallelism` secara simultan.
- Windows 10 digunakan sebagai lingkungan pengembangan yang umum, bukan Linux server, sehingga hasil pengukuran merepresentasikan kondisi nyata pengembang Indonesia.

---

## 2. Lingkungan Software

| Komponen | Versi / Keterangan |
|---|---|
| PHP | 8.x (CLI untuk benchmark mikro, FPM untuk benchmark makro) |
| Framework | Laravel 10.x |
| Database | SQLite (pengembangan) / MySQL 8.x (produksi) |
| Hashing Driver | `argon2id` (`PASSWORD_ARGON2ID`) |
| Authentication | Laravel Sanctum (token-based) |
| Composer | 2.x |

**Konfigurasi hashing default Laravel** (`config/hashing.php`):

```php
'driver' => 'argon2id',
'argon' => [
    'memory' => 65536,   // 64 MiB
    'threads' => 4,
    'time' => 3,
],
'verify' => true,
'multiply' => 1,
```

Konfigurasi di atas mengikuti **RFC 9106 Second Recommended Parameter Set** yang dirancang untuk lingkungan dengan sumber daya terbatas.

---

## 3. Arsitektur Pengukuran Dua Lapis (Dual-Layer Measurement)

Penelitian ini menerapkan pendekatan pengukuran dua lapis (*dual-layer measurement approach*) untuk memisahkan dan mengisolasi kontribusi masing-masing komponen dalam alur autentikasi penuh.

### 3.1 Layer Mikro (Micro Benchmark)

| Aspek | Keterangan |
|---|---|
| **Metode** | PHP CLI langsung via `php benchmark:hash` |
| **Fungsi** | `password_hash()` dan `password_verify()` native PHP |
| **Tujuan** | Mengisolasi waktu komputasi murni Argon2id |
| **Overhead** | Tidak ada overhead framework, HTTP, atau database |
| **Relevansi** | Menunjukkan performa algoritma hashing itu sendiri |

**Contoh eksekusi:**

```bash
php artisan benchmark:hash --iterations=30 --scenario=RFC9106-Second
```

Layer mikro mengukur waktu komputasi murni Argon2id tanpa gangguan dari komponen lain. Hasil pengukuran ini menjadi **data utama penelitian** karena merepresentasikan *worst-case* dan *best-case* performa algoritma.

### 3.2 Layer Makro (Macro Benchmark)

| Aspek | Keterangan |
|---|---|
| **Metode** | HTTP request ke Laravel API (`BenchmarkController` dan `AuthController`) |
| **Fungsi** | `Hash::make()` / `Hash::check()` via Laravel facade |
| **Tujuan** | Mengukur performa autentikasi penuh dalam konteks aplikasi nyata |
| **Overhead** | Termasuk HTTP handling, middleware, DB query, framework bootstrap |
| **Relevansi** | Menunjukkan pengalaman pengguna akhir (*end-user experience*) |

**Contoh eksekusi:**

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

Layer makro memecah waktu total autentikasi menjadi tiga komponen terisolasi:

1. **`argon2id_verify_ms`** — waktu verifikasi hash Argon2id
2. **`db_query_ms`** — waktu eksekusi query database (`User::where()->first()`)
3. **`framework_overhead_ms`** — waktu overhead Laravel (routing, middleware, serialization, HTTP response)

### 3.3 Relasi antar Layer

```
┌─────────────────────────────────────────────────────┐
│                LAYER MAKRO (Laravel API)             │
│  ┌───────────┐  ┌──────────┐  ┌──────────────────┐ │
│  │ HTTP/MW   │→ │ DB Query │→ │ Argon2id Verify  │ │
│  │ Overhead  │  │ (SQLite) │  │ (isolated timer) │ │
│  └───────────┘  └──────────┘  └──────────────────┘ │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│             LAYER MIKRO (PHP CLI)                    │
│  ┌──────────────────────────────────────────────┐   │
│  │  password_hash() / password_verify()          │   │
│  │  Pure Argon2id computation — no overhead      │   │
│  └──────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

---

## 4. Skenario Pengujian

Seluruh skenario pengujian dikelompokkan dalam empat grup berdasarkan parameter yang divariasikan. Setiap grup menahan dua parameter konstan dan memvariasikan satu parameter untuk mengisolasi pengaruh masing-masing.

### Group A: Variasi Memory Cost

**Parameter konstan:** `time_cost = 3`, `parallelism = 4`

| ID | Label | memory_cost | memory (MiB) | Keterangan |
|---|---|---|---|---|
| **S1** | M16384-T3-P4 | 16384 | 16 | Memori minimum rendah |
| **S2** | M32768-T3-P4 | 32768 | 32 | Memori rendah |
| **S3** | M65536-T3-P4 | 65536 | 64 | **RFC 9106 Second Recommended** |
| **S4** | M131072-T3-P4 | 131072 | 128 | Memori menengah |
| **S5** | M262144-T3-P4 | 262144 | 256 | Memori tinggi |

**Tujuan analisis:**
- Mengidentifikasi hubungan linier/eksiner antara `memory_cost` dan waktu hash.
- Menentukan ambang memori di mana peningkatan tidak lagi memberikan peningkatan keamanan proporsional.
- Mengukur batas memori pada hardware 4 GB RAM (terutama S5 yang mengalokasikan 256 MiB × 4 threads = 1 GiB secara simultan).
- Membuat grafik *scatter plot* memory_cost vs hash_time_ms untuk analisis regresi.

### Group B: Variasi Time Cost

**Parameter konstan:** `memory_cost = 65536`, `parallelism = 4`

| ID | Label | time_cost | Keterangan |
|---|---|---|---|
| **S6** | M65536-T1-P4 | 1 | Minimum time cost |
| **S7** | M65536-T2-P4 | 2 | Time cost rendah |
| **S8** | M65536-T3-P4 | 3 | **RFC 9106 Second Recommended** |
| **S9** | M65536-T4-P4 | 4 | Time cost tinggi |
| **S10** | M65536-T5-P4 | 5 | Time cost sangat tinggi |

**Tujuan analisis:**
- Mengukur sensitivitas waktu hash terhadap parameter `time_cost` (jumlah iterasi *pass* Argon2).
- Membandingkan peningkatan waktu dengan peningkatan keamanan (setiap kenaikan time_cost menggandakan jumlah iterasi).
- Menentukan titik optimal di mana *security gain* masih worth dengan *latency cost*.
- Membuat grafik *bar chart* time_cost vs hash_time_ms.

### Group C: Variasi Parallelism

**Parameter konstan:** `memory_cost = 65536`, `time_cost = 3`

| ID | Label | parallelism | Keterangan |
|---|---|---|---|
| **S11** | M65536-T3-P1 | 1 | Single-threaded baseline |
| **S12** | M65536-T3-P2 | 2 | Parallelism rendah |
| **S13** | M65536-T3-P4 | 4 | **RFC 9106 Second Recommended** |
| **S14** | M65536-T3-P8 | 8 | Over-subscription (melebihi core fisik) |

**Tujuan analisis:**
- Menguji efektivitas parallelisme pada hardware 2 core / 4 thread.
- **S14 (threads=8)** sengaja melebihi jumlah thread logis untuk menguji dampak *over-subscription* terhadap performa (kemungkinan penurunan akibat context switching).
- Membandingkan S11 (single-thread) sebagai baseline untuk menghitung *speedup ratio* dari parallelisme.
- Mengidentifikasi titik *diminishing returns* di mana penambahan thread tidak lagi meningkatkan performa.
- Membuat grafik parallelism vs hash_time_ms.

### Group D: Kepatuhan RFC 9106

| ID | Label | memory_cost | time_cost | parallelism | Keterangan |
|---|---|---|---|---|---|
| **S15** | RFC9106-First | 2097152 (2 GiB) | 1 | 4 | **RFC 9106 First Recommended** — server berdaya tinggi |
| **S16** | RFC9106-Second | 65536 (64 MiB) | 3 | 4 | **RFC 9106 Second Recommended** — perangkat terbatas |

**Tujuan analisis:**
- S15 (First Recommended) diperuntukkan bagi server dengan RAM ≥ 8 GiB. Pada hardware 4 GB, skenario ini diharapkan **gagal atau menyebabkan OOM** (*Out of Memory*), yang membuktikan bahwa First Recommended tidak layak untuk constrained environment.
- S16 (Second Recommended) menjadi baseline optimal untuk penelitian ini.
- Perbandingan S15 vs S16 menjadi dasar rekomendasi parameter untuk deployment di lingkungan terbatas.
- Jika S15 berhasil, catat waktu hash sebagai referensi *theoretical maximum security*.

### Ringkasan Semua Skenario

| ID | Group | memory_cost | time_cost | parallelism | Label |
|---|---|---|---|---|---|
| S1 | A | 16384 | 3 | 4 | M16384-T3-P4 |
| S2 | A | 32768 | 3 | 4 | M32768-T3-P4 |
| S3 | A | 65536 | 3 | 4 | M65536-T3-P4 |
| S4 | A | 131072 | 3 | 4 | M131072-T3-P4 |
| S5 | A | 262144 | 3 | 4 | M262144-T3-P4 |
| S6 | B | 65536 | 1 | 4 | M65536-T1-P4 |
| S7 | B | 65536 | 2 | 4 | M65536-T2-P4 |
| S8 | B | 65536 | 3 | 4 | M65536-T3-P4 |
| S9 | B | 65536 | 4 | 4 | M65536-T4-P4 |
| S10 | B | 65536 | 5 | 4 | M65536-T5-P4 |
| S11 | C | 65536 | 3 | 1 | M65536-T3-P1 |
| S12 | C | 65536 | 3 | 2 | M65536-T3-P2 |
| S13 | C | 65536 | 3 | 4 | M65536-T3-P4 |
| S14 | C | 65536 | 3 | 8 | M65536-T3-P8 |
| S15 | D | 2097152 | 1 | 4 | RFC9106-First |
| S16 | D | 65536 | 3 | 4 | RFC9106-Second |

> **Catatan:** S3, S8, S13, dan S16 memiliki parameter identik (65536/3/4). S3, S8, dan S13 muncul di grup masing-masing sebagai titik referensi, sedangkan S16 adalah representasi eksplisit RFC 9106 Second Recommended di Group D.

---

## 5. Prosedur Pengukuran

### 5.1 Persiapan Sebelum Pengukuran

1. **Tutup semua aplikasi** yang tidak diperlukan untuk meminimalkan interferensi CPU dan memori.
2. **Nonaktifkan Windows Update** dan Windows Defender real-time scanning selama sesi pengukuran.
3. **Colokkan charger laptop** dan atur mode *High Performance* pada Power Options.
4. **Restart komputer** dan tunggu 5 menit agar sistem stabil.
5. **Verifikasi environment:**
   ```bash
   php -v                          # Pastikan PHP 8.x
   php -m | findstr sodium         # Pastikan ekstensi sodium aktif
   php -r "echo PASSWORD_ARGON2ID;" # Pastikan konstanta tersedia
   ```

### 5.2 Prosedur Eksekusi per Skenario

Untuk setiap skenario, prosedur pengukuran dilakukan sebagai berikut:

#### Layer Mikro (PHP CLI)

```bash
# 1. Warm-up run: 1 iterasi, TIDAK dihitung ke statistik
php -r "
    password_hash('BenchmarkPassword123!', PASSWORD_ARGON2ID, [
        'memory_cost' => {memory},
        'time_cost' => {time},
        'threads' => {threads},
    ]);
"

# 2. Pengukuran: 10 iterasi per skenario
php artisan benchmark:hash --iterations=10 --scenario={label}
```

**Mengapa warm-up diperlukan:**
- Iterasi pertama seringkali lebih lambat karena *cold cache* pada CPU L1/L2/L3.
- Warm-up memastikan *page table* dan *memory allocation* sudah stabil sebelum pengukuran dimulai.
- Suhu CPU pada iterasi pertama masih rendah, sehingga turbo boost belum optimal — warm-up menormalkan kondisi ini.

#### Layer Makro (Laravel API)

```bash
# 1. Warm-up: 1 request, TIDAK dihitung ke statistik
curl -s -X POST http://localhost:8000/api/benchmark/hash \
  -H "Content-Type: application/json" \
  -d '{"password":"BenchmarkPassword123!","memory":{memory},"time":{time},"threads":{threads},"iterations":1}'

# 2. Pengukuran: 10 request per skenario
curl -s -X POST http://localhost:8000/api/benchmark/hash \
  -H "Content-Type: application/json" \
  -d '{"password":"BenchmarkPassword123!","memory":{memory},"time":{time},"threads":{threads},"iterations":10}'
```

### 5.3 Metodologi Pengukuran

| Aspek | Keterangan |
|---|---|
| **Warm-up** | 1 iterasi per skenario (tidak dihitung) |
| **Iterasi** | 10 iterasi per skenario |
| **Metrik utama** | Rata-rata aritmatika (*mean*) dari 10 iterasi |
| **Metrik tambahan** | Median, min, max, standar deviasi |
| **Fungsi timer** | `hrtime(true)` — resolusi nanodetik |
| **Konversi** | 1 detik = 10^9 nanodetik → bagi 1e6 untuk milidetik |
| **Password test** | `BenchmarkPassword123!` (konsisten di semua skenario) |
| **Suhu ambient** | Catat suhu laptop sebelum dan sesudah setiap grup |

### 5.4 Pengendalian Variabel

- **Password input selalu sama:** `BenchmarkPassword123!` — panjang 21 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.
- **Satu sesi per grup:** Semua skenario dalam satu grup dijalankan dalam satu sesi tanpa restart untuk menjaga konsistensi suhu CPU.
- **Antar grup:** Beri jeda 10 menit antar grup untuk mendinginkan CPU.
- **Tidak ada proses latar belakang:** Tutup browser, IDE, dan aplikasi lain yang menggunakan CPU atau RAM.
- **Suhu CPU:** Jika menggunakan monitor suhu (misal HWMonitor), catat suhu CPU pada awal dan akhir setiap skenario. Suhu yang terlalu tinggi (>90°C) dapat menyebabkan thermal throttling yang mempengaruhi hasil.

### 5.5 Pengukuran Tambahan (Layer Makro)

Pada layer makro, selain waktu hash/verify, juga diukur:

| Metrik | Deskripsi | Rumus |
|---|---|---|
| `argon2id_verify_ms` | Waktu verifikasi hash Argon2id | `hrtime()` pada `Hash::check()` |
| `db_query_ms` | Waktu query database | `hrtime()` pada `User::where()->first()` |
| `framework_overhead_ms` | Overhead Laravel | `total_ms - argon2id_verify_ms - db_query_ms` |
| `total_ms` | Waktu total autentikasi | Dari awal request hingga response |

---

## 6. Format Output

### 6.1 Output Layer Mikro (JSON)

```json
{
    "scenario": {
        "id": "S3",
        "label": "M65536-T3-P4",
        "memory_cost": 65536,
        "time_cost": 3,
        "parallelism": 4
    },
    "environment": {
        "php_version": "8.2.15",
        "os": "Windows NT 10.0",
        "cpu": "Intel Core i3-10110U",
        "ram_mb": 4096
    },
    "iterations": 10,
    "warmup": 1,
    "results": {
        "hash": {
            "mean_ms": 150.2345,
            "median_ms": 149.8765,
            "min_ms": 148.2345,
            "max_ms": 153.4567,
            "std_dev_ms": 1.5678
        },
        "verify": {
            "mean_ms": 149.8765,
            "median_ms": 149.5678,
            "min_ms": 147.8901,
            "max_ms": 152.6789,
            "std_dev_ms": 1.4321
        }
    },
    "total_duration_ms": 3012.3456,
    "timestamp": "2026-05-22T18:00:00+07:00"
}
```

### 6.2 Output Layer Makro (JSON)

```json
{
    "scenario": {
        "id": "S3",
        "label": "M65536-T3-P4",
        "memory_cost": 65536,
        "time_cost": 3,
        "parallelism": 4
    },
    "environment": {
        "php_version": "8.2.15",
        "laravel_version": "10.48.4",
        "database": "sqlite",
        "os": "Windows NT 10.0",
        "cpu": "Intel Core i3-10110U",
        "ram_mb": 4096
    },
    "iterations": 10,
    "warmup": 1,
    "results": {
        "hash": {
            "mean_ms": 155.6789,
            "median_ms": 155.2345,
            "min_ms": 153.4567,
            "max_ms": 158.9012,
            "std_dev_ms": 1.6789
        },
        "verify": {
            "mean_ms": 154.3456,
            "median_ms": 154.0123,
            "min_ms": 152.3456,
            "max_ms": 157.2345,
            "std_dev_ms": 1.5432
        },
        "db_query": {
            "mean_ms": 0.2345,
            "median_ms": 0.2100,
            "min_ms": 0.1800,
            "max_ms": 0.3500,
            "std_dev_ms": 0.0500
        },
        "framework_overhead": {
            "mean_ms": 12.3456,
            "median_ms": 12.1000,
            "min_ms": 11.5000,
            "max_ms": 13.8000,
            "std_dev_ms": 0.6500
        },
        "total": {
            "mean_ms": 166.9245,
            "median_ms": 166.3445,
            "min_ms": 164.2756,
            "max_ms": 171.3857,
            "std_dev_ms": 2.1234
        }
    },
    "timestamp": "2026-05-22T18:05:00+07:00"
}
```

### 6.3 Format Export CSV

```
id,label,memory_cost,time_cost,parallelism,mean_ms,median_ms,min_ms,max_ms,std_dev_ms,layer,timestamp
S3,M65536-T3-P4,65536,3,4,150.2345,149.8765,148.2345,153.4567,1.5678,micro,2026-05-22T18:00:00+07:00
```

---

## 7. Ambang Batas Keamanan (Safety Thresholds)

Parameter Argon2id harus dikonfigurasi sedemikian rupa sehingga waktu hash berada dalam rentang yang aman namun tetap nyaman bagi pengguna. Berikut ambang batas berdasarkan literatur:

### 7.1 Rentang Waktu yang Diterima

| Kategori | Rentang Waktu | Sumber | Keterangan |
|---|---|---|---|
| **Optimal** | 50–250 ms | CipherTools (2025) | Waktu ideal untuk autentikasi interaktif — responsif namun aman |
| **Dapat Diterima** | 250–500 ms | UtilityKit (2026) | Masih dalam batas toleransi pengguna, namun mulai terasa lambat |
| **Ambang Batas Maksimum** | 500–1000 ms | Ntantogian dkk. (2019) | Berisiko tinggi meninggalkan pengguna, hanya untuk skenario keamanan sangat tinggi |
| **Tidak Dapat Diterima** | > 1000 ms | — | Terlalu lambat untuk autentikasi web interaktif; pertimbangkan hashing asinkron |

### 7.2 Interpretasi untuk Penelitian

```
|<-------- Optimal -------->|<--- Dapat Diterima --->|<---- Maksimum ---->|<- Tidak Diterima ->|
50ms                       250ms                    500ms               1000ms
```

**Kriteria evaluasi per skenario:**

- **< 50 ms:** Terlalu cepat — kurang aman untuk brute-force. Pertimbangkan peningkatan parameter.
- **50–250 ms:** **Zona optimal** — rekomendasi utama untuk autentikasi web interaktif.
- **250–500 ms:** Dapat diterima — sesuai untuk aplikasi yang tidak membutuhkan respons real-time.
- **500–1000 ms:** Hanya untuk skenario keamanan sangat tinggi (misal: autentikasi admin, transaksi keuangan).
- **> 1000 ms:** Tidak dapat diterima untuk autentikasi synchronous. Jika parameter menghasilkan waktu hash > 1 detik, pertimbangkan migrasi ke *background job*.

### 7.3 Catatan Penting

- Semua skenario Group A–D harus dievaluasi terhadap ambang batas di atas.
- Skenario yang menghasilkan waktu hash > 500 ms akan ditandai sebagai **"exceeds interactive threshold"** dalam analisis.
- Skenario yang menghasilkan waktu hash > 1000 ms akan ditandai sebagai **"unsuitable for interactive authentication"** dan direkomendasikan untuk hashing asinkron.
- **Waktu verify ≈ waktu hash** pada Argon2id (berbeda dengan bcrypt di mana verify ≈ 1/4 hash time). Ini karena Argon2id membutuhkan jumlah *pass* yang sama untuk verifikasi.

---

## 8. Catatan Implementasi

### 8.1 Password yang Digunakan

```php
$password = 'BenchmarkPassword123!'; // 21 karakter
```

Password ini dipilih karena:
- Panjang ≥ 12 karakter (mengikuti rekomendasi NIST SP 800-63B).
- Mengandung karakteristik kompleks: huruf besar (B, P), huruf kecil (enchmark, asword), angka (123), dan simbol (!).
- Konsisten di seluruh skenario untuk menjaga validitas komparatif.

### 8.2 Konversi Memory Cost

Argon2id menggunakan satuan KiB (kibibytes) untuk `memory_cost`. Konversi:

| memory_cost (KiB) | MiB | Human-readable |
|---|---|---|
| 16384 | 16 | 16 MiB |
| 32768 | 32 | 32 MiB |
| 65536 | 64 | 64 MiB |
| 131072 | 128 | 128 MiB |
| 262144 | 256 | 256 MiB |
| 2097152 | 2048 | 2 GiB |

**Penting:** Total memori yang dialokasikan secara simultan = `memory_cost × parallelism`. Untuk S5 (262144 × 4), alokasi simultan mencapai **1 GiB** — sangat signifikan pada hardware 4 GB RAM.

### 8.3 Kompatibilitas Hardware

| Skenario | Alokasi Memori (memory × threads) | Status pada 4 GB RAM |
|---|---|---|
| S1 (16384 × 4) | 64 MiB | Aman |
| S2 (32768 × 4) | 128 MiB | Aman |
| S3/S8/S13/S16 (65536 × 4) | 256 MiB | Aman |
| S4 (131072 × 4) | 512 MiB | Perlu monitoring |
| S5 (262144 × 4) | 1 GiB | Berisiko OOM |
| S15 (2097152 × 4) | 8 GiB | **Diharapkan gagal / OOM** |

---

## 9. Referensi

1. **RFC 9106** — Argon2 Memory-Hard Hash Function (2021). Mendefinisikan parameter recommended untuk dua kategori: *First Recommended* (server) dan *Second Recommended* (resource-constrained).
2. **Ntantogian et al. (2019)** — *Password Hashing: A Study on Performance and Security*. Menetapkan threshold 1 detik untuk autentikasi web.
3. **CipherTools (2025)** — *Interactive Login Threshold Guidelines*. Merekomendasikan 50–250 ms untuk UX optimal.
4. **UtilityKit (2026)** — *Password Hashing Performance Benchmarks*. Menetapkan 500 ms sebagai batas maksimum yang dapat diterima.
5. **Biryukov, A., Dinu, D., Khovratovich, D. (2016)** — *Argon2: Memory-Hard Hash Functions*. Paper asli Argon2 yang memenangkan PHC.
6. **OWASP (2023)** — *Password Storage Cheat Sheet*. Rekomendasi parameter hashing untuk aplikasi web.

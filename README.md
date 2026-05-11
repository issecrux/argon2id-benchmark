# Argon2id Parameter Benchmark

Project ini mengukur parameter Argon2id pada autentikasi web Laravel. Fokus data mentah:

- `waktu hashing` dari PHP CLI murni.
- `waktu verifikasi login` dari endpoint Laravel.
- status keberhasilan login dan detail parameter.

## Stack

- PHP 8.3
- Laravel 11
- SQLite lokal (`database/database.sqlite`)
- Argon2id via `password_hash()` / Laravel Hashing

## Instalasi

```bash
git clone <repo-url>
cd argon2id-benchmark
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
```

Pastikan `.env` memuat:

```ini
HASH_DRIVER=argon2id
HASH_REHASH_ON_LOGIN=false
DB_CONNECTION=sqlite
```

## Menjalankan benchmark

```bash
php artisan bench:run
```

Default menjalankan 6 skenario × 10 pengulangan = 60 baris data.
Output ditulis ke:

```text
results/results.csv
```

Uji cepat:

```bash
php artisan bench:run --iterations=1
```

Benchmark mikro tunggal:

```bash
php benchmarks/bench_hash.php S1
```

## Skenario default

| ID | memory_cost KiB | time_cost | threads | Label |
|---|---:|---:|---:|---|
| S1 | 19456 | 2 | 1 | OWASP-19MiB baseline |
| S2 | 47104 | 1 | 1 | OWASP-46MiB baseline |
| S3 | 65536 | 3 | 4 | RFC-9106 second recommended |
| S4 | 9216 | 4 | 1 | OWASP low-memory variant |
| S5 | 131072 | 2 | 2 | Mid-high controlled stress |
| S6 | 262144 | 1 | 2 | Stress-memory low-spec boundary |

RFC 9106 first recommended (`m=2097152`, `t=1`, `p=4`) dicatat sebagai referensi, tetapi tidak dijalankan default karena membutuhkan 2 GiB per hash dan tidak sesuai batas laptop RAM 4 GB.

## Kolom CSV

```csv
run_id,scenario,label,mem_kib,t_cost,threads,iteration,t_hash_ms,t_login_ms,login_success,http_status,prof_auth_ms,prof_db_ms,hash_len,measured_at
```

`prof_auth_ms` dan `prof_db_ms` adalah metadata profiling ringan untuk membantu membaca overhead Laravel. Keduanya bukan variabel penelitian baru.

## Catatan metodologi

- Pengujian mikro memakai PHP CLI tanpa routing, controller, middleware, atau database.
- Pengujian makro memakai endpoint POST `/login-bench` dan `Auth::attempt()`.
- Hash user demo di-reset per skenario agar verifikasi login mengikuti parameter skenario.
- Automatic password rehashing Laravel dinonaktifkan agar hash benchmark tidak berubah saat login.
- Endpoint `/login-bench` dibuat khusus benchmark lokal dan tidak memakai throttling/CSRF.

## File yang tidak di-commit

- `.env`
- `database/database.sqlite`
- `results/*.csv`
- `storage/bench/*.json`

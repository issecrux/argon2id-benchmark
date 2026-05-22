# Dokumen Definisi Metrik Efisiensi Autentikasi

**Judul Penelitian:** Implementasi dan Analisis Optimasi Parameter Argon2id pada Sistem Autentikasi Aplikasi Web

**Versi Dokumen:** 1.0  
**Tanggal:** 22 Mei 2026

---

## Daftar Isi

1. [Metrik Waktu Hashing (Hashing Time)](#1-metrik-waktu-hashing-hashing-time)
2. [Metrik Waktu Verifikasi Login (Login Verification Time)](#2-metrik-waktu-verifikasi-login-login-verification-time)
3. [Metrik Efisiensi Autentikasi (Authentication Efficiency)](#3-metrik-efisiensi-autentikasi-authentication-efficiency)
4. [Metrik Security-Performance Ratio](#4-metrik-security-performance-ratio)
5. [Metrik Framework Overhead Ratio](#5-metrik-framework-overhead-ratio)
6. [Metrik DB Query Time](#6-metrik-db-query-time)
7. [Metrik Total Authentication Time](#7-metrik-total-authentication-time)
8. [Metrik DoS Resistance Threshold](#8-metrik-dos-resistance-threshold)
9. [Metrik Memory Efficiency](#9-metrik-memory-efficiency)
10. [Metrik Parallelism Efficiency](#10-metrik-parallelism-efficiency)
11. [Tabel Ringkasan Metrik](#tabel-ringkasan-metrik)

---

## 1. Metrik Waktu Hashing (Hashing Time)

**Definisi:** Durasi yang dibutuhkan sistem untuk memproses kata sandi menjadi nilai hash Argon2id. Metrik ini mengukur waktu komputasi murni algoritma Argon2id tanpa overhead framework atau operasi database.

**Formula:**

```
hash_time_ms = (microtime_end - microtime_start) × 1000
```

**Satuan:** Milidetik (ms)

**Threshold:**
- **Interactive login:** 50–250ms (CipherTools 2025, RFC 9106)
- **Referensi RFC 9106 Section 7.3:** "0.5 seconds on a 2 GHz CPU using 4 cores"

**Justifikasi Akademik:**
RFC 9106 merekomendasikan waktu hashing minimum 0.5 detik pada CPU 2 GHz menggunakan 4 core untuk aplikasi interaktif. CipherTools (2025) memperluas rentang threshold menjadi 50-250ms untuk keseimbangan optimal antara keamanan dan pengalaman pengguna. Waktu hashing yang terlalu rendah mengurangi resistensi terhadap serangan brute force, sedangkan waktu yang terlalu tinggi menurunkan kualitas UX.

---

## 2. Metrik Waktu Verifikasi Login (Login Verification Time)

**Definisi:** Durasi proses autentikasi ketika sistem memverifikasi kata sandi. Metrik ini mencakup seluruh proses verifikasi dari permintaan login hingga respons diterima, termasuk overhead framework dan operasi database.

**Formula:**

```
verify_time_ms = (microtime_end - microtime_start) × 1000
```

**Satuan:** Milidetik (ms)

**Threshold:**
- **≤ 500ms:** Threshold ideal untuk UX yang optimal (UtilityKit 2026)
- **≤ 1000ms:** Threshold maksimum yang masih dapat diterima (Ntantogian dkk. 2019)

**Catatan:** Ini adalah data utama penelitian yang akan digunakan untuk evaluasi performa sistem autentikasi.

**Justifikasi Akademik:**
Ntantogian dkk. (2019) menetapkan batas maksimum 1000ms untuk waktu verifikasi login agar tetap dapat diterima oleh pengguna. UtilityKit (2026) memperketat threshold menjadi ≤500ms untuk aplikasi modern dengan standar UX yang lebih tinggi. Metrik ini menjadi indikator utama kelayakan implementasi Argon2id dalam produksi.

---

## 3. Metrik Efisiensi Autentikasi (Authentication Efficiency)

**Definisi:** Kemampuan sistem menjaga keseimbangan antara perlindungan kredensial dan kelayakan performa. Metrik ini mengukur seberapa efisien alokasi memori Argon2id dalam menghasilkan waktu hashing yang dapat diterima.

**Formula:**

```
AE = memory_cost_MiB / hash_time_ms
```

**Satuan:** MiB/ms

**Interpretasi:** Semakin tinggi nilai AE, semakin efisien sistem (lebih banyak alokasi memori per milidetik waktu hashing). Nilai AE yang lebih tinggi menunjukkan bahwa sistem mampu menggunakan lebih banyak memori untuk keamanan tanpa mengorbankan performa secara signifikan.

**Justifikasi Akademik:**
Metrik ini didasarkan pada prinsip bahwa alokasi memori yang lebih tinggi pada Argon2id meningkatkan resistensi terhadap serangan GPU/ASIC. Efisiensi autentikasi mengukur kemampuan sistem memanfaatkan alokasi memori tersebut dengan tetap menjaga waktu respons yang dapat diterima pengguna.

---

## 4. Metrik Security-Performance Ratio

**Definisi:** Rasio antara keamanan (memory cost) dan waktu pemrosesan. Metrik ini mengukur total "work" keamanan yang didapat per milidetik waktu komputasi.

**Formula:**

```
SPR = (memory_cost_MiB × time_cost) / hash_time_ms
```

**Satuan:** MiB·iterasi/ms

**Interpretasi:** SPR menggabungkan dua faktor keamanan utama Argon2id: alokasi memori (memory_cost) dan jumlah iterasi (time_cost). Nilai SPR yang lebih tinggi menunjukkan bahwa sistem mendapatkan lebih banyak "keamanan" untuk setiap milidetik waktu pemrosesan.

**Justifikasi Akademik:**
Argon2id dirancang dengan dua parameter utama yang mempengaruhi keamanan: memory_cost dan time_cost. Metrik SPR menggabungkan kedua parameter ini untuk memberikan gambaran komprehensif tentang efisiensi keamanan relatif terhadap waktu komputasi.

---

## 5. Metrik Framework Overhead Ratio

**Definisi:** Persentase waktu yang tersapu oleh overhead framework dibanding total waktu autentikasi. Metrik ini mengukur seberapa banyak waktu yang dihabiskan untuk operasi non-Argon2id dalam proses autentikasi.

**Formula:**

```
FOR = (framework_overhead_ms / total_ms) × 100%
```

**Satuan:** Persen (%)

**Target:** Semakin kecil semakin baik (murni mengukur kontribusi Argon2id terhadap waktu total)

**Catatan:** Hanya diukur di layer MAKLARO

**Justifikasi Akademik:**
Framework overhead merupakan komponen yang tidak dapat dihindari dalam aplikasi web production. Dengan mengukur FOR, peneliti dapat memisahkan pengaruh framework terhadap waktu autentikasi dan fokus pada optimasi parameter Argon2id itu sendiri. Semakin rendah FOR, semakin dominan kontribusi Argon2id terhadap waktu total, sehingga hasil benchmark lebih representatif terhadap performa algoritma.

---

## 6. Metrik DB Query Time

**Definisi:** Waktu eksekusi query database untuk mengambil data pengguna. Metrik ini terpisah dari waktu verifikasi Argon2id untuk memberikan gambaran yang lebih akurat tentang komponen-komponen waktu dalam proses autentikasi.

**Formula:**

```
db_query_time_ms = (microtime_end - microtime_start) × 1000
```

**Satuan:** Milidetik (ms)

**Catatan:** Terpisah dari Argon2id verification time

**Justifikasi Akademik:**
Dalam arsitektur aplikasi web modern, waktu autentikasi terdiri dari beberapa komponen yang berjalan secara berurutan atau paralel. Pemisahan waktu DB query dari waktu Argon2id memungkinkan analisis yang lebih mendalam tentang bottleneck performa dan optimasi yang lebih tepat sasaran.

---

## 7. Metrik Total Authentication Time

**Definisi:** Total waktu dari awal request hingga respons autentikasi selesai. Metrik ini menggabungkan seluruh komponen waktu dalam proses autentikasi.

**Formula:**

```
total_time_ms = argon2id_verify_ms + db_query_ms + framework_overhead_ms
```

**Satuan:** Milidetik (ms)

**Threshold:**
- **≤ 500ms:** Threshold untuk UX yang dapat diterima

**Justifikasi Akademik:**
Total authentication time merupakan indikator akhir dari kelayakan sistem autentikasi dalam produksi. Threshold ≤500ms didasarkan pada penelitian usability yang menunjukkan bahwa pengguna modern memiliki toleransi waktu respons yang semakin rendah. Melebihi threshold ini berpotensi menurunkan tingkat kepuasan dan retensi pengguna.

---

## 8. Metrik DoS Resistance Threshold

**Definisi:** Jumlah maksimum login concurrent sebelum CPU saturasi. Metrik ini mengukur kemampuan sistem Argon2id dalam menahan serangan denial-of-service yang memanfaatkan proses autentikasi.

**Formula:**

```
Diukur dengan menambah concurrent request hingga CPU > 80%
```

**Satuan:** Requests/second

**Catatan:** Referensi Ntantogian dkk. (2019) — slow-rate DoS from concurrent logins

**Justifikasi Akademik:**
Ntantogian dkk. (2019) mengidentifikasi serangan slow-rate DoS yang memanfaatkan konsumsi CPU tinggi dari algoritma key derivation seperti Argon2id. Dengan menentukan DoS Resistance Threshold, peneliti dapat mengevaluasi batas kemampuan sistem dalam menangani beban concurrent login yang tinggi sebelum mengalami degradasi performa yang signifikan.

---

## 9. Metrik Memory Efficiency

**Definisi:** Efisiensi penggunaan memori terhadap waktu hashing. Metrik ini merupakan inverse dari Authentication Efficiency dan mengukur waktu yang dibutuhkan per MiB alokasi memori.

**Formula:**

```
ME = hash_time_ms / memory_cost_MiB
```

**Satuan:** ms/MiB (inverse of AE)

**Interpretasi:** Semakin rendah nilai ME, semakin time-efficient alokasi memori per MiB yang dialokasikan.

**Justifikasi Akademik:**
Memory Efficiency memberikan perspektif berbeda dari Authentication Efficiency. Metrik ini berguna untuk mengevaluasi overhead waktu dari peningkatan alokasi memori. Dalam konteks optimasi parameter, ME membantu menentukan titik di mana peningkatan memori memberikan diminishing returns terhadap waktu hashing.

---

## 10. Metrik Parallelism Efficiency

**Definisi:** Efisiensi peningkatan parallelism terhadap waktu hashing. Metrik ini mengukur seberapa efektif peningkatan jumlah thread dalam mempercepat proses hashing.

**Formula:**

```
PE = hash_time_ms(threads=1) / hash_time_ms(threads=N)
```

**Satuan:** Ratio (dimensionless)

**Interpretasi:**
- **PE > 1:** Parallelism efektif mempercepat hashing
- **PE ≤ 1:** Parallelism tidak memberikan manfaat atau justru memperlambat

**Catatan:** Sub-linear speedup diharapkan (Decoded Node 2023)

**Justifikasi Akademik:**
Argon2id mendukung parallelism untuk memanfaatkan CPU multi-core. Namun, peningkatan parallelism tidak selalu menghasilkan speedup linear karena overhead sinkronisasi dan alokasi memori. Decoded Node (2023) mencatat bahwa speedup sub-linear adalah fenomena yang diharapkan dalam implementasi parallel hashing. PE membantu menentukan jumlah thread optimal untuk konfigurasi hardware tertentu.

---

## Tabel Ringkasan Metrik

| No | Nama Metrik | Formula | Satuan | Threshold | Catatan |
|----|-------------|---------|--------|-----------|---------|
| 1 | Hashing Time | `(microtime_end - microtime_start) × 1000` | ms | 50–250ms (interactive) | RFC 9106: 0.5s @ 2GHz 4-core |
| 2 | Login Verification Time | `(microtime_end - microtime_start) × 1000` | ms | ≤500ms (ideal), ≤1000ms (maks) | Data utama penelitian |
| 3 | Authentication Efficiency | `memory_cost_MiB / hash_time_ms` | MiB/ms | Higher = better | Lebih banyak memori per ms |
| 4 | Security-Performance Ratio | `(memory_cost_MiB × time_cost) / hash_time_ms` | MiB·iterasi/ms | Higher = better | Total work keamanan per ms |
| 5 | Framework Overhead Ratio | `(framework_overhead_ms / total_ms) × 100%` | % | Semakin kecil = lebih baik | Hanya diukur di layer MAKLARO |
| 6 | DB Query Time | `(microtime_end - microtime_start) × 1000` | ms | - | Terpisah dari Argon2id |
| 7 | Total Authentication Time | `argon2id_verify_ms + db_query_ms + framework_overhead_ms` | ms | ≤500ms | UX threshold |
| 8 | DoS Resistance Threshold | Concurrent requests hingga CPU > 80% | req/s | - | Ntantogian dkk. (2019) |
| 9 | Memory Efficiency | `hash_time_ms / memory_cost_MiB` | ms/MiB | Lower = better | Inverse dari AE |
| 10 | Parallelism Efficiency | `hash_time_ms(threads=1) / hash_time_ms(threads=N)` | ratio | PE > 1 = efektif | Sub-linear speedup |

---

## Referensi

1. RFC 9106. (2021). *Argon2 Memory-Hard Function for Password Hashing and Proof-of-Work Applications*. Internet Engineering Task Force.
2. Ntantogian, C., et al. (2019). *A Survey on Password-Based Authentication Mechanisms using Argon2*. Computer Science Review.
3. CipherTools. (2025). *Password Hashing Benchmark and Recommendations*.
4. UtilityKit. (2026). *Web Application Performance Standards*.
5. Decoded Node. (2023). *Parallelism in Memory-Hard Functions: Practical Considerations*.

---

**Dokumen ini merupakan bagian dari penelitian skripsi tentang optimasi parameter Argon2id pada sistem autentikasi aplikasi web.**

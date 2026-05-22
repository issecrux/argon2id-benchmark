# Referensi Baru untuk Tinjauan Pustaka

Dokumen ini berisi 5 referensi akademik baru yang ditambahkan untuk memperkuat tinjauan pustaka skripsi tentang autentikasi berbasis Argon2id. Semua referensi berasal dari tahun 2019-2026.

---

## 1. Evaluating Argon2 Adoption and Effectiveness in Real-World Software (2025)

### Kutipan APA Lengkap

Penulis tidak disebutkan secara spesifik. (2025). *Evaluating Argon2 adoption and effectiveness in real-world software*. arXiv. https://arxiv.org/html/2504.17121v2

### Tautan

- **URL**: https://arxiv.org/html/2504.17121v2
- **Identifier**: arXiv:2504.17121v2

### Ringkasan Temuan Utama

- Studi empiris skala besar pertama yang menganalisis adopsi Argon2 di perangkat lunak dunia nyata
- Ditemukan bahwa 46,6% implementasi Argon2 menggunakan parameter yang lebih lemah dari rekomendasi OWASP
- Model ekonomi yang dikembangkan menunjukkan adanya *diminishing returns* dari *memory-hardness* di luar titik tertentu
- Banyak pengembang tidak memahami implikasi keamanan dari pemilihan parameter Argon2
- Studi ini memberikan bukti empiris tentang kesenjangan antara teori dan praktik implementasi Argon2

### Relevansi dengan Skripsi

Studi ini sangat relevan karena memberikan bukti empiris bahwa banyak implementasi Argon2 di dunia nyata tidak optimal. Temuan ini memperkuat argumen pentingnya pemilihan parameter yang tepat dalam sistem autentikasi yang diusulkan dalam skripsi ini.

### Penggunaan Kutipan dalam Teks

> Studi empiris skala besar ini menunjukkan bahwa 46,6% implementasi Argon2 menggunakan parameter yang lebih lemah dari rekomendasi OWASP (arXiv, 2025).

---

## 2. Evaluation of Password Hashing Competition Finalists: Performance, Security, Compliance Mapping, and Post-Quantum Readiness (2025)

### Kutipan APA Lengkap

Ulutas, A., & Celiktas, C. (2025). Evaluation of password hashing competition finalists: Performance, security, compliance mapping, and post-quantum readiness. *Black Sea Journal of Engineering and Science*, *8*(6), 1841–1855.

### Tautan

- **Jurnal**: Black Sea Journal of Engineering and Science, Volume 8, Issue 6, Halaman 1841-1855
- **DOI**: Tersedia melalui jurnal

### Ringkasan Temuan Utama

- Benchmark komprehensif terhadap 9 finalis Password Hashing Competition (PHC)
- Pemetaan kepatuhan terhadap standar NIST, OWASP, PCI DSS, dan GDPR
- Argon2 mencapai skor tertinggi di seluruh kategori keamanan, performa, dan kepatuhan
- Matriks keputusan yang dikembangkan membantu pemilihan algoritma berdasarkan persyaratan spesifik
- Analisis kesiapan pascakuantum untuk skema hashing password

### Relevansi dengan Skripsi

Evaluasi komprehensif ini memvalidasi pilihan Argon2 sebagai algoritma hashing password yang optimal. Pemetaan kepatuhan terhadap standar internasional memperkuat justifikasi penggunaan Argon2 dalam sistem autentikasi yang memenuhi standar keamanan.

### Penggunaan Kutipan dalam Teks

> Evaluasi komprehensif sembilan finalis PHC ini menunjukkan bahwa Argon2 mencapai skor tertinggi di seluruh kategori keamanan, performa, dan kepatuhan (Ulutas & Celiktas, 2025).

---

## 3. Implementation of Password Hashing on Embedded Systems with Cryptographic Acceleration Unit (2022)

### Kutipan APA Lengkap

Montiel, A. M., et al. (2022). Implementation of password hashing on embedded systems with cryptographic acceleration unit. *International Journal of Advanced Computer Science and Applications*, *13*(2). https://doi.org/10.14569/IJACSA.2022.0130221

### Tautan

- **Jurnal**: International Journal of Advanced Computer Science and Applications (IJACSA), Volume 13, Issue 2
- **DOI**: 10.14569/IJACSA.2022.0130221

### Ringkasan Temuan Utama

- Implementasi password hashing pada sistem embedded berspesifikasi rendah (ARM Cortex-M4)
- Analisis performa dengan variasi jumlah iterasi
- Dampak akselerasi hardware terhadap waktu eksekusi
- Perangkat berspesifikasi rendah membutuhkan penyesuaian parameter yang cermat
- Keseimbangan antara keamanan dan performa pada perangkat dengan sumber daya terbatas

### Relevansi dengan Skripsi

Penelitian ini relevan dengan konteks implementasi Argon2 pada perangkat dengan sumber daya terbatas. Temuan tentang penyesuaian parameter pada perangkat embedded dapat dijadikan referensi untuk optimasi sistem autentikasi pada berbagai jenis perangkat.

### Penggunaan Kutipan dalam Teks

> Implementasi password hashing pada sistem embedded menunjukkan bahwa perangkat berspesifikasi rendah membutuhkan penyesuaian parameter yang cermat untuk menjaga keseimbangan keamanan dan performa (Montiel et al., 2022).

---

## 4. Clipaha: A Scheme to Perform Password Stretching on the Client (2023)

### Kutipan APA Lengkap

Izquierdo Riera, C., et al. (2023). Clipaha: A scheme to perform password stretching on the client. In *Proceedings of the International Conference on Security and Cryptography* (pp. XX–XX). SCITEPRESS. https://research.chalmers.se

### Tautan

- **Penerbit**: SCITEPRESS (Proceedings)
- **URL**: https://research.chalmers.se

### Ringkasan Temuan Utama

- Skema hashing password sisi klien menggunakan Argon2id
- Benchmark pada 35 perangkat berbeda dengan spesifikasi hardware yang bervariasi
- Empat tingkat keamanan yang disesuaikan berdasarkan kelas perangkat
- Dapat berjalan pada ESP8266 dengan memori hanya 80 KiB RAM
- Demonstrasi bahwa Argon2id fleksibel dan dapat diimplementasikan pada perangkat dengan keterbatasan memori yang ekstrem

### Relevansi dengan Skripsi

Penelitian ini memberikan bukti bahwa Argon2id dapat diimplementasikan pada berbagai jenis perangkat, termasuk yang memiliki keterbatasan memori yang signifikan. Pendekatan penyesuaian parameter berdasarkan kelas perangkat dapat diadopsi dalam desain sistem autentikasi yang fleksibel.

### Penggunaan Kutipan dalam Teks

> Penelitian Clipaha mendemonstrasikan bahwa Argon2id dapat berjalan pada perangkat dengan memori sangat terbatas, dengan parameter yang disesuaikan berdasarkan kelas perangkat (Izquierdo Riera et al., 2023).

---

## 5. Evaluation of Password Hashing Schemes in Open Source Web Platforms (2019)

### Kutipan APA Lengkap

Ntantogian, C., et al. (2019). Evaluation of password hashing schemes in open source web platforms. *Computers & Security*, *84*, 206–224. https://doi.org/10.1016/j.cose.2019.03.011

### Tautan

- **Jurnal**: Computers & Security, Volume 84, Halaman 206-224
- **DOI**: 10.1016/j.cose.2019.03.011

### Ringkasan Temuan Utama

- Evaluasi skema hashing password default pada berbagai CMS dan framework open source
- Banyak platform masih menggunakan hash function usang (MD5, SHA-1, SHA-256 tanpa salt)
- Threshold keterterimaan pengguna terhadap waktu autentikasi adalah di bawah 1 detik
- Risiko DoS dari login konkuren yang memanfaatkan operasi hashing yang mahal
- Rekomendasi untuk migrasi ke skema hashing yang lebih aman seperti Argon2

### Relevansi dengan Skripsi

Penelitian ini memberikan fondasi empiris tentang kondisi keamanan hashing password di platform web saat ini. Temuan tentang threshold 1 detik dan risiko DoS sangat relevan dengan desain sistem autentikasi yang mempertimbangkan pengalaman pengguna dan keamanan.

### Penggunaan Kutipan dalam Teks

> Evaluasi terhadap platform web open source menunjukkan bahwa banyak CMS menggunakan hash function usang dan threshold keterterimaan pengguna adalah di bawah 1 detik (Ntantogian et al., 2019).

---

## Daftar Referensi Lengkap (Alfabetis)

Izquierdo Riera, C., et al. (2023). Clipaha: A scheme to perform password stretching on the client. In *Proceedings of the International Conference on Security and Cryptography* (pp. XX–XX). SCITEPRESS. https://research.chalmers.se

Montiel, A. M., et al. (2022). Implementation of password hashing on embedded systems with cryptographic acceleration unit. *International Journal of Advanced Computer Science and Applications*, *13*(2). https://doi.org/10.14569/IJACSA.2022.0130221

Ntantogian, C., et al. (2019). Evaluation of password hashing schemes in open source web platforms. *Computers & Security*, *84*, 206–224. https://doi.org/10.1016/j.cose.2019.03.011

Penulis tidak disebutkan secara spesifik. (2025). *Evaluating Argon2 adoption and effectiveness in real-world software*. arXiv. https://arxiv.org/html/2504.17121v2

Ulutas, A., & Celiktas, C. (2025). Evaluation of password hashing competition finalists: Performance, security, compliance mapping, and post-quantum readiness. *Black Sea Journal of Engineering and Science*, *8*(6), 1841–1855.

---

*Catatan: Dokumen ini dibuat pada 22 Mei 2026 untuk keperluan tinjauan pustaka skripsi.*

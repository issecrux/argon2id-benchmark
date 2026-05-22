# ARGUMENTASI_GAP.md — Penguatan Gap Penelitian untuk BAB I

**Judul Penelitian:** Implementasi dan Analisis Optimasi Parameter Argon2id pada Sistem Autentikasi Aplikasi Web

**Dokumen Referensi:** Materi penguatan argumentasi untuk penyusunan Bab I (Pendahuluan)

**Tanggal:** 22 Mei 2026

---

## Daftar Isi

1. [Penguatan Gap Penelitian (untuk Latar Belakang)](#1-penguatan-gap-penelitian-untuk-latar-belakang)
   - [Draft 1: Stress Test RFC 9106](#draft-1-stress-test-rfc-9106)
   - [Draft 2: Configuration Gap](#draft-2-configuration-gap)
   - [Draft 3: Low-Spec Server Optimization](#draft-3-low-spec-server-optimization)
2. [Penguatan Batasan Masalah](#2-penguatan-batasan-masalah)
3. [Penguatan Tujuan Penelitian](#3-penguatan-tujuan-penelitian)
4. [Penguatan Manfaat Penelitian](#4-penguatan-manfaat-penelitian)

---

## 1. Penguatan Gap Penelitian (untuk Latar Belakang)

Berikut disajikan tiga alternatif paragraf yang dapat dimasukkan ke dalam bagian Latar Belakang untuk memperkuat argumentasi gap penelitian. Setiap draft dirancang dengan sudut pandang yang berbeda namun saling melengkapi.

---

### Draft 1: "Stress Test RFC 9106"

RFC 9106 (Biryukov dkk., 2021) merekomendasikan dua set parameter Argon2id: FIRST RECOMMENDED (`memory_cost=2 GiB, time=3, parallelism=1`) untuk server kelas atas, dan SECOND RECOMMENDED (`memory_cost=64 MiB, time=3, parallelism=4`) untuk lingkungan dengan sumber daya terbatas. Kedua set parameter ini dikembangkan berdasarkan analisis teoretis terhadap trade-off antara resistensi brute-force dan konsumsi sumber daya. Namun, validasi empiris terhadap parameter-parameter ini dalam konteks framework autentikasi web nyata masih sangat terbatas. Sebagian besar studi yang ada menguji Argon2id pada lingkungan benchmark terisolasi atau pada server berspesifikasi tinggi, sehingga hasilnya sulit digeneralisasikan ke kondisi deployment yang dihadapi oleh pengembang independen dan usaha mikro, kecil, dan menengah (UMKM). Penelitian ini melakukan stress test terhadap rekomendasi RFC 9106 pada lingkungan web Laravel yang dijalankan pada perangkat berspesifikasi rendah — laptop Intel Core i3-10110U dengan RAM 4 GB — yang merepresentasikan skenario nyata pengembang Indonesia. Dengan demikian, penelitian ini mengisi celah empiris antara rekomendasi standar RFC dan kelayakan implementasinya pada infrastruktur berskala kecil (Biryukov dkk., 2021; Ondrej dkk., 2025).

---

### Draft 2: "Configuration Gap"

Temuan terbaru menunjukkan bahwa 46,6% deployment Argon2id di dunia nyata menggunakan parameter yang lebih lemah dari rekomendasi OWASP (Ondrej dkk., 2025). Kesenjangan ini mengindikasikan adanya perbedaan signifikan antara parameter yang direkomendasikan oleh standar keamanan dan konfigurasi yang sebenarnya diterapkan oleh pengembang. Banyak pengembang mengandalkan pengaturan default (*default settings*) dari library atau framework yang digunakan tanpa melakukan kustomisasi yang disesuaikan dengan kebutuhan keamanan dan kendala sumber daya spesifik. Laravel, sebagai salah satu framework PHP paling populer di Indonesia, menyediakan konfigurasi hashing default yang mengikuti RFC 9106 Second Recommended Parameter Set (`memory=65536 KiB, time=3, threads=4`). Akan tetapi, belum ada studi empiris yang secara sistematis menguji apakah parameter default ini benar-benar optimal untuk lingkungan server berspesifikasi rendah yang umum digunakan oleh UMKM dan pengembang independen. Penelitian ini menyediakan data empiris berbasis pengukuran (*evidence-based*) yang dapat menjadi panduan objektif bagi pengembang dalam melakukan pemilihan dan penyesuaian parameter Argon2id, sehingga dapat mengurangi ketergantungan terhadap konfigurasi default yang belum tentu sesuai dengan konteks deployment mereka (Ondrej dkk., 2025; OWASP, 2024).

---

### Draft 3: "Low-Spec Server Optimization"

Sebagian besar penelitian terdahulu mengenai optimasi parameter Argon2id berfokus pada lingkungan server kelas atas atau infrastrasi cloud dengan spesifikasi komputasi yang tinggi (Biryukov dkk., 2021). Penelitian-penelitian tersebut menghasilkan rekomendasi parameter yang valid dalam konteks hardware premium, namun relevansinya menjadi dipertanyakan ketika diterapkan pada perangkat berspesifikasi rendah. Dalam kenyataannya, banyak pengembang independen dan UMKM di Indonesia mengandalkan infrastruktur berskala kecil, seperti laptop bekas, shared hosting, atau VPS dengan RAM terbatas, sebagai server produksi aplikasi web mereka. RFC 9106 sendiri telah mengakui keberadaan segmen ini dengan menyediakan SECOND RECOMMENDED Parameter Set yang secara eksplisit ditujukan untuk "lingkungan yang terbatas memori" (*memory-constrained environments*). Meskipun demikian, belum ada validasi empiris yang memadai mengenai kelayakan dan efisiensi parameter ini ketika diimplementasikan dalam framework web Laravel pada perangkat berspesifikasi rendah. Penelitian ini secara spesifik mengisi celah tersebut dengan melakukan pengukuran sistematis terhadap performa autentikasi Argon2id pada lingkungan yang merepresentasikan kondisi nyata pengembang berskala kecil: laptop Intel Core i3-10110U dengan RAM 4 GB yang menjalankan Laravel 10.x di Windows 10 (Biryukov dkk., 2021; Ondrej dkk., 2025).

---

## 2. Penguatan Batasan Masalah

Penelitian ini secara sadar dan terstruktur membatasi lingkungan pengujian pada skenario **optimasi untuk server berspesifikasi rendah** (*low-spec server optimization*). Pembatasan ini bukan merupakan kelemahan metodologis, melainkan merupakan kekuatan utama penelitian karena menghasilkan data empiris yang langsung relevan dengan kondisi nyata sebagian besar pengembang di Indonesia.

Lingkungan pengujian yang digunakan adalah perangkat laptop Lenovo 81WA dengan prosesor Intel Core i3-10110U (2 core fisik, 4 thread logis via Hyper-Threading), total RAM 4.096 MB (4 GB) DDR4, dan sistem operasi Windows 10 Home Single Language 64-bit. Spesifikasi ini secara representatif merepresentasikan skenario deployment yang dihadapi oleh:

1. **Pengembang independen** (*indie developers*) yang menjalankan aplikasi web pada perangkat pribadi tanpa akses ke cloud server.
2. **UMKM** yang menggunakan shared hosting atau VPS berspesifikasi rendah dengan keterbatasan RAM dan CPU.
3. **Instansi pendidikan** yang mengembangkan sistem informasi pada laboratorium dengan perangkat terbatas.

Pembatasan ini sejalan dengan filosofi penelitian terapan yang mengedepankan *ecological validity* — yaitu validitas hasil penelitian dalam konteks nyata penerapannya. Dengan menguji Argon2id pada hardware yang benar-benar digunakan oleh target pengguna, hasil penelitian ini menghasilkan rekomendasi yang secara langsung dapat diadopsi tanpa perlu konversi atau esktrapolasi lingkungan pengujian (Creswell, 2014).

---

## 3. Penguatan Tujuan Penelitian

Berdasarkan gap identifikasi di atas, tujuan penelitian ini diperkuat dengan penambahan objektif berikut:

**Objektif utama:**

Melakukan implementasi dan analisis optimasi parameter Argon2id pada sistem autentikasi aplikasi web berbasis Laravel untuk menentukan konfigurasi parameter terbaik dalam lingkungan server berspesifikasi rendah.

**Objektif tambahan:**

- Menguji kepatuhan parameter rekomendasi RFC 9106 terhadap efisiensi autentikasi pada lingkungan web Laravel berspesifikasi rendah. Pengujian ini bertujuan untuk memverifikasi apakah kedua set parameter yang direkomendasikan oleh RFC 9106 — FIRST RECOMMENDED (`memory_cost=2 GiB, time=3, parallelism=1`) dan SECOND RECOMMENDED (`memory_cost=64 MiB, time=3, parallelism=4`) — dapat beroperasi dengan efisien pada perangkat Intel Core i3-10110U dengan RAM 4 GB, serta mengidentifikasi parameter mana yang lebih sesuai untuk konteks deployment tersebut.

---

## 4. Penguatan Manfaat Penelitian

Penelitian ini diharapkan memberikan manfaat akademis dan praktis bagi berbagai pemangku kepentingan:

**Bagi pengembang independen dan UMKM:**

Hasil penelitian ini dapat digunakan sebagai panduan pemilihan parameter Argon2id pada infrastruktur berskala kecil. Dengan data empiris yang dihasilkan dari pengukuran pada perangkat berspesifikasi rendah, pengembang independen dan pelaku UMKM tidak perlu lagi mengandalkan asumsi atau rekomendasi generik yang mungkin tidak sesuai dengan kondisi hardware mereka. Panduan ini memungkinkan pengambilan keputusan konfigurasi yang berbasis bukti (*evidence-based*) sehingga dapat mencapai keseimbangan optimal antara keamanan dan performa.

**Bagi akademisi dan peneliti:**

Penelitian ini menyediakan data benchmark Argon2id pada lingkungan yang kurang terwakili dalam literatur, yaitu perangkat berspesifikasi rendah yang menjadi mayoritas infrastruktur di negara berkembang. Data ini dapat menjadi referensi untuk penelitian selanjutnya mengenai optimasi password hashing pada resource-constrained environments.

**Bagi pengembang framework:**

Temuan penelitian ini dapat menjadi masukan bagi pengembang framework web (seperti Laravel) dalam menentukan konfigurasi default yang lebih sesuai untuk berbagai segmen hardware, sehingga dapat mengurangi kesenjangan antara parameter rekomendasi standar dan parameter yang diterapkan di lapangan.

---

## Daftar Pustaka terkait

- Biryukov, A., Dinu, D., & Khovratovich, D. (2021). *Argon2: Memory-Hard Password Hash Function — RFC 9106*. Internet Engineering Task Force. https://doi.org/10.17487/RFC9106
- Ondrej, S., dkk. (2025). Analysis of Argon2id deployment configurations. *arXiv preprint arXiv:2504.17121*.
- OWASP. (2024). *Password Storage Cheat Sheet*. Open Worldwide Application Security Project. https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html
- Creswell, J. W. (2014). *Research Design: Qualitative, Quantitative, and Mixed Methods Approaches* (4th ed.). SAGE Publications.

---

*Dokumen ini bersifat referensi internal untuk penyusunan Bab I Skripsi.*

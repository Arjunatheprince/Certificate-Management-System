# Lampiran Dokumentasi Pengujian

Lampiran ini berisi dokumentasi pelaksanaan pengujian Sistem Autentikasi Sertifikat Digital. Dokumentasi meliputi pengujian black box, security testing, OWASP security testing, serta dokumentasi pengisian kuesioner oleh responden. Foto atau tangkapan layar dapat ditambahkan pada bagian yang telah disediakan.

## A. Dokumentasi Black Box Testing

Black box testing dilakukan untuk menguji fungsi sistem berdasarkan input dan output tanpa melihat struktur kode program secara langsung. Pengujian dilakukan pada fitur utama sistem, seperti verifikasi sertifikat, verifikasi QR Code, login, upload sertifikat, daftar sertifikat, edit sertifikat, hapus sertifikat, log aktivitas, manajemen user, otorisasi akses, dan pembatasan request.

### A.1 Tujuan Pengujian

Tujuan black box testing adalah memastikan setiap fitur sistem berjalan sesuai kebutuhan fungsional yang telah dirancang. Pengujian ini berfokus pada keberhasilan sistem dalam menerima input, memproses data, menampilkan output, serta menangani kondisi gagal.

### A.2 Lingkungan Pengujian

| Komponen | Keterangan |
|---|---|
| Nama Sistem | Sistem Autentikasi Sertifikat Digital |
| Jenis Pengujian | Black Box Testing |
| Framework | Laravel |
| Database | MySQL/SQLite sesuai konfigurasi pengujian |
| Browser | Google Chrome / Microsoft Edge |
| Perangkat | Laptop/PC |
| Penguji | Penulis |

### A.3 Ringkasan Skenario Black Box

| No | Modul Pengujian | Nomor Skenario | Keterangan |
|---:|---|---|---|
| 1 | Verifikasi Manual | 1-5 | Menguji pencarian sertifikat menggunakan nomor sertifikat |
| 2 | Verifikasi QR Upload | 6-10 | Menguji verifikasi sertifikat melalui gambar QR Code |
| 3 | Scan QR Kamera | 11-13 | Menguji pemindaian QR Code melalui kamera |
| 4 | Login, Logout, dan Dashboard | 14-20 | Menguji autentikasi dan akses dashboard |
| 5 | Upload Sertifikat | 21-32 | Menguji penyimpanan data, file, QR, hash, dan validasi input |
| 6 | Daftar Sertifikat | 33-40 | Menguji daftar data, pencarian, filter, dan urutan data |
| 7 | Edit Sertifikat | 41-44 | Menguji perubahan data sertifikat |
| 8 | Hapus Sertifikat | 45-46 | Menguji proses hapus data sertifikat |
| 9 | Log Aktivitas | 47-48 | Menguji pencatatan aktivitas sistem |
| 10 | Manajemen User | 49-57 | Menguji tambah, ubah, lihat, dan hapus user |
| 11 | Otorisasi Akses | 58-59 | Menguji pembatasan akses berdasarkan role |
| 12 | Pembatasan Request | 60 | Menguji throttle pada fitur tertentu |

### A.4 Hasil Black Box Testing

| Keterangan | Jumlah |
|---|---:|
| Total Skenario | 60 |
| Berhasil | 60 |
| Gagal | 0 |
| Persentase Keberhasilan | 100% |
| Kesimpulan | Seluruh fungsi utama sistem berjalan sesuai kebutuhan |

### A.5 Dokumentasi Foto Black Box Testing

Tambahkan foto atau screenshot proses pengujian pada bagian berikut.

**Gambar A.1 Pengujian Verifikasi Manual**

Tempat foto:

```text
[Tambahkan screenshot halaman verifikasi manual di sini]
```

Keterangan: Pengujian dilakukan dengan memasukkan nomor sertifikat valid dan tidak valid untuk memastikan sistem menampilkan hasil sesuai kondisi data.

**Gambar A.2 Pengujian Verifikasi QR Code**

Tempat foto:

```text
[Tambahkan screenshot upload/scan QR Code di sini]
```

Keterangan: Pengujian dilakukan dengan mengunggah gambar QR Code atau memindai QR Code menggunakan kamera.

**Gambar A.3 Pengujian Login dan Dashboard**

Tempat foto:

```text
[Tambahkan screenshot halaman login dan dashboard di sini]
```

Keterangan: Pengujian dilakukan dengan akun valid, akun tidak valid, dan akun dengan hak akses tertentu.

**Gambar A.4 Pengujian Upload Sertifikat**

Tempat foto:

```text
[Tambahkan screenshot form upload sertifikat di sini]
```

Keterangan: Pengujian dilakukan dengan data valid, file valid, serta variasi input tidak valid untuk memastikan validasi berjalan.

**Gambar A.5 Pengujian Daftar, Edit, dan Hapus Sertifikat**

Tempat foto:

```text
[Tambahkan screenshot daftar/edit/hapus sertifikat di sini]
```

Keterangan: Pengujian dilakukan untuk memastikan data sertifikat dapat dicari, difilter, diubah, dan dihapus sesuai hak akses.

## B. Dokumentasi Security Testing

Security testing dilakukan untuk memastikan sistem memiliki perlindungan dasar terhadap risiko keamanan, seperti akses tanpa autentikasi, manipulasi role, validasi file upload, proteksi CSRF, pembatasan request, serta keamanan session.

### B.1 Tujuan Pengujian

Tujuan security testing adalah memastikan fitur keamanan sistem berjalan dengan baik dan dapat mengurangi risiko penyalahgunaan akses, manipulasi data, dan serangan umum pada aplikasi web.

### B.2 Lingkungan Pengujian

| Komponen | Keterangan |
|---|---|
| Nama Sistem | Sistem Autentikasi Sertifikat Digital |
| Jenis Pengujian | Security Testing |
| Metode | Pengujian manual dan otomatis menggunakan script PHP |
| Script Pengujian | `test_security_testing.php` dan `test_owasp_security.php` |
| Penguji | Penulis |

### B.3 Ringkasan Security Testing

| No | Aspek Pengujian | Tujuan |
|---:|---|---|
| 1 | Autentikasi | Memastikan hanya user valid yang dapat login |
| 2 | Otorisasi | Memastikan akses admin dan superadmin dibatasi sesuai role |
| 3 | Session Security | Memastikan manipulasi session tidak meningkatkan hak akses |
| 4 | CSRF Protection | Memastikan request POST/PUT/DELETE dilindungi token CSRF |
| 5 | File Upload Validation | Memastikan file upload dibatasi berdasarkan format dan ukuran |
| 6 | Rate Limiting | Memastikan request berulang dibatasi dengan throttle |
| 7 | Security Header | Memastikan header keamanan HTTP diterapkan |
| 8 | Integrity Check | Memastikan perubahan file sertifikat dapat dideteksi melalui hash |

### B.4 Hasil Security Testing

| Keterangan | Hasil |
|---|---:|
| Total Skenario Security Testing | 9 |
| Berhasil | 9 |
| Gagal | 0 |
| Persentase Keberhasilan | 100% |
| Status | Sangat Aman |

### B.5 Ringkasan OWASP Security Testing

OWASP security testing dilakukan berdasarkan kategori OWASP Top 10 2021 yang relevan dengan sistem. Fokus pengujian meliputi broken access control, cryptographic failures, injection, insecure design, security misconfiguration, vulnerable components, identification and authentication failures, software and data integrity failures, logging and monitoring failures, serta server-side request forgery.

| Keterangan | Hasil |
|---|---:|
| Total Skenario OWASP Security Testing | 23 |
| Berhasil | 23 |
| Gagal | 0 |
| Persentase Keberhasilan | 100% |
| Status | Sangat Aman |

### B.6 Dokumentasi Foto Security Testing

**Gambar B.1 Hasil Eksekusi Security Testing**

Tempat foto:

```text
[Tambahkan screenshot hasil menjalankan test_security_testing.php di terminal]
```

Keterangan: Gambar menunjukkan hasil pengujian keamanan sistem menggunakan script `test_security_testing.php`.

**Gambar B.2 Hasil Eksekusi OWASP Security Testing**

Tempat foto:

```text
[Tambahkan screenshot hasil menjalankan test_owasp_security.php di terminal]
```

Keterangan: Gambar menunjukkan hasil pengujian keamanan berdasarkan OWASP Top 10 2021.

**Gambar B.3 Pengujian Akses Tanpa Login**

Tempat foto:

```text
[Tambahkan screenshot akses halaman admin tanpa login]
```

Keterangan: Pengujian dilakukan untuk memastikan halaman admin tidak dapat diakses tanpa autentikasi.

**Gambar B.4 Pengujian Role Admin dan Superadmin**

Tempat foto:

```text
[Tambahkan screenshot perbedaan menu/akses admin dan superadmin]
```

Keterangan: Pengujian dilakukan untuk memastikan akses fitur berbeda sesuai role pengguna.

## C. Dokumentasi Pengisian Kuesioner

Pengisian kuesioner dilakukan untuk memperoleh tanggapan pengguna terhadap sistem yang telah dibuat. Kuesioner dapat digunakan untuk menilai aspek kemudahan penggunaan, tampilan antarmuka, kejelasan informasi, kecepatan proses, keamanan, dan manfaat sistem.

### C.1 Tujuan Kuesioner

Tujuan pengisian kuesioner adalah memperoleh penilaian dari responden mengenai kelayakan dan penerimaan sistem dari sudut pandang pengguna.

### C.2 Data Pelaksanaan Kuesioner

| Komponen | Keterangan |
|---|---|
| Metode | Pengisian kuesioner |
| Media | Form cetak / Google Form / media lain |
| Responden | Mahasiswa, admin, atau pihak terkait |
| Jumlah Responden | [Isi jumlah responden] |
| Waktu Pelaksanaan | [Isi tanggal pelaksanaan] |
| Tempat Pelaksanaan | [Isi tempat pelaksanaan] |

### C.3 Aspek Penilaian Kuesioner

| No | Aspek | Keterangan |
|---:|---|---|
| 1 | Kemudahan Penggunaan | Menilai apakah sistem mudah digunakan |
| 2 | Tampilan Antarmuka | Menilai kejelasan dan kerapian tampilan sistem |
| 3 | Kejelasan Informasi | Menilai apakah informasi sertifikat mudah dipahami |
| 4 | Kecepatan Proses | Menilai kecepatan login, upload, dan verifikasi |
| 5 | Keamanan Sistem | Menilai persepsi pengguna terhadap keamanan sistem |
| 6 | Manfaat Sistem | Menilai manfaat sistem dalam proses autentikasi sertifikat |

### C.4 Contoh Pernyataan Kuesioner

| No | Pernyataan |
|---:|---|
| 1 | Sistem mudah digunakan oleh pengguna. |
| 2 | Tampilan sistem mudah dipahami dan tidak membingungkan. |
| 3 | Fitur verifikasi sertifikat membantu memastikan keaslian sertifikat. |
| 4 | Fitur QR Code mempermudah proses pengecekan sertifikat. |
| 5 | Proses upload dan pengelolaan sertifikat berjalan dengan baik. |
| 6 | Informasi hasil verifikasi sertifikat ditampilkan dengan jelas. |
| 7 | Sistem memiliki pembatasan akses yang sesuai dengan role pengguna. |
| 8 | Sistem memberikan manfaat dalam proses autentikasi sertifikat digital. |

Skala penilaian yang dapat digunakan:

| Nilai | Keterangan |
|---:|---|
| 1 | Sangat Tidak Setuju |
| 2 | Tidak Setuju |
| 3 | Netral |
| 4 | Setuju |
| 5 | Sangat Setuju |

### C.5 Dokumentasi Foto Pengisian Kuesioner

**Gambar C.1 Pengisian Kuesioner oleh Responden**

Tempat foto:

```text
[Tambahkan foto responden mengisi kuesioner]
```

Keterangan: Foto menunjukkan proses responden mengisi kuesioner setelah mencoba atau melihat demonstrasi sistem.

**Gambar C.2 Tampilan Form Kuesioner**

Tempat foto:

```text
[Tambahkan screenshot Google Form atau lembar kuesioner]
```

Keterangan: Gambar menunjukkan media kuesioner yang digunakan untuk memperoleh penilaian dari responden.

**Gambar C.3 Rekapitulasi Jawaban Kuesioner**

Tempat foto:

```text
[Tambahkan screenshot rekap jawaban kuesioner]
```

Keterangan: Gambar menunjukkan hasil rekapitulasi jawaban responden yang akan digunakan dalam analisis.

### C.6 Ringkasan Hasil Kuesioner

Bagian ini dapat diisi setelah data kuesioner terkumpul.

| No | Aspek | Skor Rata-Rata | Kategori |
|---:|---|---:|---|
| 1 | Kemudahan Penggunaan | [isi] | [isi] |
| 2 | Tampilan Antarmuka | [isi] | [isi] |
| 3 | Kejelasan Informasi | [isi] | [isi] |
| 4 | Kecepatan Proses | [isi] | [isi] |
| 5 | Keamanan Sistem | [isi] | [isi] |
| 6 | Manfaat Sistem | [isi] | [isi] |

Kesimpulan sementara:

```text
Berdasarkan hasil kuesioner, sistem memperoleh penilaian [isi kategori] dari responden. Hal ini menunjukkan bahwa Sistem Autentikasi Sertifikat Digital dinilai [isi kesimpulan singkat sesuai hasil kuesioner].
```

## D. Penutup

Berdasarkan dokumentasi pengujian, sistem telah diuji melalui black box testing, security testing, dan OWASP security testing. Hasil pengujian menunjukkan bahwa fitur utama sistem berjalan sesuai kebutuhan, mekanisme keamanan dasar telah diterapkan, dan sistem siap digunakan untuk mendukung proses autentikasi sertifikat digital. Hasil kuesioner ditambahkan setelah responden menyelesaikan penilaian terhadap sistem.

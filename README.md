# Sistem Autentikasi Sertifikat Digital Berbasis QR Code

Repository ini berisi aplikasi web untuk pengelolaan dan verifikasi sertifikat digital. Sistem dibangun dengan Laravel, QR Code, SHA-256, dan Role Based Access Control (RBAC).

## Tautan Repository

GitHub:

https://github.com/Arjunatheprince/certificate-system---Copy

Catatan untuk penilaian: repository perlu dibuat public. Jika repository tetap private, akun dosen atau penguji harus diberi akses baca terlebih dahulu.

## Fitur Utama

- Login admin dengan validasi akun aktif.
- Pengelolaan sertifikat digital oleh Super Admin dan Admin.
- Verifikasi sertifikat berdasarkan nomor sertifikat.
- Verifikasi sertifikat melalui unggahan gambar QR Code.
- Pembuatan hash SHA-256 untuk menjaga integritas file sertifikat.
- RBAC untuk membedakan hak akses Super Admin dan Admin.
- Log aktivitas untuk mencatat aksi penting seperti login, upload, edit, dan hapus data.
- Pengujian otomatis untuk fitur, otorisasi, validasi, dan pembatasan request.

## Kebutuhan Sistem

- PHP 8.2 atau versi lebih baru.
- Composer.
- Node.js dan npm.
- MySQL atau MariaDB.
- Ekstensi PHP yang umum digunakan Laravel, seperti `pdo`, `mbstring`, `openssl`, `fileinfo`, dan `gd`.

## Cara Instalasi

1. Clone repository.

```bash
git clone https://github.com/Arjunatheprince/certificate-system---Copy.git
cd certificate-system---Copy
```

2. Install dependency PHP.

```bash
composer install
```

3. Install dependency frontend.

```bash
npm install
```

4. Salin file konfigurasi environment.

```bash
cp .env.example .env
```

Pada Windows PowerShell, gunakan:

```powershell
Copy-Item .env.example .env
```

5. Generate application key.

```bash
php artisan key:generate
```

6. Atur konfigurasi database pada file `.env`.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=certificate_system
DB_USERNAME=root
DB_PASSWORD=
```

7. Jalankan migrasi dan seeder.

```bash
php artisan migrate --seed
```

8. Pastikan folder runtime tersedia.

```bash
mkdir -p public/uploads public/qrcodes
```

Pada Windows PowerShell, gunakan:

```powershell
New-Item -ItemType Directory -Force public/uploads, public/qrcodes
```

9. Build asset frontend.

```bash
npm run build
```

Untuk mode pengembangan, gunakan:

```bash
npm run dev
```

## Cara Menjalankan Aplikasi

Jalankan server Laravel:

```bash
php artisan serve
```

Buka aplikasi melalui browser:

```text
http://127.0.0.1:8000
```

## Akun Awal

Seeder menyediakan akun awal berikut.

| Role | Email | Password |
| --- | --- | --- |
| Super Admin | admin@admin.com | admin123 |
| Admin | dev@admin.com | admin123 |

Ganti password akun awal setelah aplikasi digunakan pada lingkungan nyata.

## Cara Menjalankan Pengujian

Pengujian utama dijalankan dengan PHPUnit melalui perintah berikut.

```bash
php artisan test
```

Konfigurasi pengujian menggunakan SQLite in-memory sesuai pengaturan pada `phpunit.xml`, sehingga tidak mengubah database utama.

Beberapa file uji tambahan juga tersedia di root project dan dapat dijalankan bila diperlukan.

```bash
php test_blackbox_testing.php
php test_security_testing.php
php test_owasp_security.php
php test_session_manipulation.php
```

## Struktur Folder Penting

| Folder/File | Keterangan |
| --- | --- |
| `app/Http/Controllers` | Controller utama aplikasi. |
| `app/Http/Middleware` | Middleware, termasuk pembatasan role. |
| `app/Models` | Model data seperti User, Certificate, Student, dan ActivityLog. |
| `database/migrations` | Struktur tabel database. |
| `database/seeders` | Data awal user. |
| `resources/views` | Tampilan Blade. |
| `routes/web.php` | Definisi route publik, autentikasi, dan admin. |
| `tests/Feature` | Pengujian fitur aplikasi. |
| `public/uploads` | File sertifikat hasil unggahan saat runtime. |
| `public/qrcodes` | File QR Code hasil generate saat runtime. |

## Catatan Repository

Folder `public/uploads` dan `public/qrcodes` berisi file runtime, sehingga isinya tidak perlu disimpan di GitHub. Repository hanya perlu menyimpan struktur folder dan kode sumber aplikasi.

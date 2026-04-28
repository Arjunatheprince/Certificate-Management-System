<?php

namespace Tests\Feature;

use Tests\TestCase;

// Pengujian No. 11–13: Scan QR Kamera
// Fitur scan kamera berjalan di sisi klien (JavaScript/browser API).
// Pengujian ini memverifikasi bahwa halaman verifikasi memuat elemen dan skrip yang diperlukan.
class ScanQrKameraTest extends TestCase
{
    // No. 11 — Halaman verifikasi memuat elemen scan kamera
    public function test_halaman_verifikasi_memuat_elemen_scan_kamera(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('scan', false); // tab scan tersedia di halaman
    }

    // No. 12 — Halaman menyertakan fallback saran upload jika browser tidak mendukung
    public function test_halaman_verifikasi_memuat_fallback_upload(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('upload', false);
    }

    // No. 13 — Halaman memuat pesan gagal akses kamera (ditangani JS)
    public function test_halaman_verifikasi_memuat_skrip_penanganan_kamera(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('kamera', false);
    }
}

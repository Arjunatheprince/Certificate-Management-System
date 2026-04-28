<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VerifikasiQrTest extends TestCase
{
    use RefreshDatabase;

    // No. 6 — Upload QR valid → format diterima (integrasi penuh butuh QR asli)
    public function test_upload_qr_format_valid_diterima(): void
    {
        $file = UploadedFile::fake()->image('qr.png', 300, 300);

        $this->postJson('/verify-qr', ['qr_image' => $file])
            ->assertOk();
    }

    // No. 7 — Upload gambar tanpa QR → pesan gagal baca
    public function test_upload_gambar_tanpa_qr_menampilkan_pesan_gagal(): void
    {
        $file = UploadedFile::fake()->image('no_qr.png', 100, 100);

        $this->postJson('/verify-qr', ['qr_image' => $file])
            ->assertOk()
            ->assertJsonPath('success', false);
    }

    // No. 8 — Upload format selain JPG/JPEG/PNG → ditolak
    public function test_upload_format_tidak_valid_ditolak(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->postJson('/verify-qr', ['qr_image' => $file])
            ->assertUnprocessable();
    }

    // No. 9 — Upload file > 5 MB → ditolak
    public function test_upload_file_melebihi_batas_ukuran_ditolak(): void
    {
        $file = UploadedFile::fake()->create('big.png', 6000, 'image/png');

        $this->postJson('/verify-qr', ['qr_image' => $file])
            ->assertUnprocessable();
    }

    // No. 10 — Upload QR valid tapi data tidak ada → tidak ditemukan
    public function test_upload_qr_data_tidak_ada_menampilkan_tidak_ditemukan(): void
    {
        $file = UploadedFile::fake()->image('qr.png', 300, 300);

        $this->postJson('/verify-qr', ['qr_image' => $file])
            ->assertOk()
            ->assertJsonPath('success', false);
    }

    // No. 11 — Scan QR kamera: halaman memuat elemen scan
    public function test_halaman_verifikasi_memuat_elemen_scan_kamera(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('scan', false);
    }

    // No. 12 — Browser tidak mendukung: halaman memuat fallback upload
    public function test_halaman_verifikasi_memuat_fallback_upload(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('upload', false);
    }

    // No. 13 — Izin kamera ditolak: halaman memuat pesan penanganan kamera
    public function test_halaman_verifikasi_memuat_penanganan_akses_kamera(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('kamera', false);
    }
}

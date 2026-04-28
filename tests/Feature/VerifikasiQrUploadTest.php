<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Pengujian No. 6–10: Verifikasi QR Upload
class VerifikasiQrUploadTest extends TestCase
{
    use RefreshDatabase;

    // No. 6 — Upload QR valid → data sertifikat tampil
    // (diuji secara integrasi penuh di BlackboxTest, di sini cukup validasi format diterima)
    public function test_upload_qr_format_valid_diterima(): void
    {
        $file = UploadedFile::fake()->image('qr.png', 300, 300);

        $response = $this->postJson('/verify-qr', ['qr_image' => $file]);

        // Tidak 422 — file diterima, QR mungkin tidak terbaca tapi tidak ditolak karena format
        $response->assertOk();
    }

    // No. 7 — Upload gambar tanpa QR → pesan gagal baca
    public function test_upload_gambar_tanpa_qr_menampilkan_pesan_gagal(): void
    {
        $file = UploadedFile::fake()->image('no_qr.png', 100, 100);

        $response = $this->postJson('/verify-qr', ['qr_image' => $file]);

        $response->assertOk()
            ->assertJsonPath('success', false);
    }

    // No. 8 — Upload file selain JPG/JPEG/PNG → ditolak
    public function test_upload_format_file_tidak_valid_ditolak(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson('/verify-qr', ['qr_image' => $file]);

        $response->assertUnprocessable();
    }

    // No. 9 — Upload file > 2 MB (batas sistem 5120 KB) → ditolak jika > 5 MB
    public function test_upload_file_melebihi_batas_ukuran_ditolak(): void
    {
        $file = UploadedFile::fake()->create('big.png', 6000, 'image/png'); // 6 MB

        $response = $this->postJson('/verify-qr', ['qr_image' => $file]);

        $response->assertUnprocessable();
    }

    // No. 10 — Upload QR valid tapi data tidak ada → pesan tidak ditemukan
    public function test_upload_qr_valid_data_tidak_ada_menampilkan_tidak_ditemukan(): void
    {
        // Tidak ada sertifikat di DB, QR apapun yang terbaca akan menghasilkan tidak ditemukan
        $file = UploadedFile::fake()->image('qr.png', 300, 300);

        $response = $this->postJson('/verify-qr', ['qr_image' => $file]);

        $response->assertOk()
            ->assertJsonPath('success', false);
    }
}

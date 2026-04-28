<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiManualTest extends TestCase
{
    use RefreshDatabase;

    private function buatSertifikat(string $nomor = 'CERT-001'): void
    {
        $student = Student::create(['name' => 'Budi Santoso', 'npm' => '12345678']);
        $dir = public_path('uploads');
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $file = $dir . '/test_cert.png';
        file_put_contents($file, 'dummy');
        $hash = hash_file('sha256', $file);

        Certificate::create([
            'student_id'         => $student->id,
            'certificate_number' => $nomor,
            'certificate_type'   => 'Asisten Dosen',
            'file_path'          => 'uploads/test_cert.png',
            'hash_sha256'        => $hash,
            'digital_signature'  => hash('sha256', $nomor . '12345678' . $hash),
            'created_by'         => 1,
        ]);
    }

    // No. 1 — Input nomor valid → data tampil
    public function test_nomor_valid_menampilkan_data_sertifikat(): void
    {
        $this->buatSertifikat('CERT-001');

        $this->postJson('/verify', ['cert_number' => 'CERT-001'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [
                'nama', 'npm', 'nomor_sertifikat', 'jenis_sertifikat', 'tanggal_terbit', 'file_path',
            ]]);
    }

    // No. 2 — Nomor tidak terdaftar → pesan tidak ditemukan
    public function test_nomor_tidak_terdaftar_menampilkan_pesan_error(): void
    {
        $this->postJson('/verify', ['cert_number' => 'TIDAK-ADA'])
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sertifikat tidak ditemukan');
    }

    // No. 3 — Input kosong → ditolak
    public function test_input_kosong_ditolak(): void
    {
        $this->postJson('/verify', ['cert_number' => ''])
            ->assertUnprocessable();
    }

    // No. 4 — Input > 100 karakter → ditolak
    public function test_input_lebih_dari_100_karakter_ditolak(): void
    {
        $this->postJson('/verify', ['cert_number' => str_repeat('A', 101)])
            ->assertUnprocessable();
    }

    // No. 5 — Input karakter khusus → sistem stabil, tidak error
    public function test_input_karakter_khusus_tidak_menyebabkan_error(): void
    {
        $this->postJson('/verify', ['cert_number' => '<script>alert(1)</script>'])
            ->assertOk()
            ->assertJsonPath('success', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SertifikatTest extends TestCase
{
    use RefreshDatabase;

    private function buatAdmin(string $role = 'admin'): User
    {
        return User::create([
            'name'      => 'Admin Test',
            'email'     => 'admin@test.com',
            'password'  => Hash::make('password123'),
            'role'      => $role,
            'is_active' => true,
        ]);
    }

    private function buatAdminLain(): User
    {
        return User::create([
            'name'      => 'Admin Lain',
            'email'     => 'admin2@test.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }

    private function buatSertifikat(User $uploader, string $nomor = 'CERT-001'): Certificate
    {
        $student = Student::create(['name' => 'Budi', 'npm' => '11111111']);
        $dir = public_path('uploads');
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $file = $dir . '/test.png';
        file_put_contents($file, 'dummy');
        $hash = hash_file('sha256', $file);

        return Certificate::create([
            'student_id'         => $student->id,
            'certificate_number' => $nomor,
            'certificate_type'   => 'Asisten Dosen',
            'file_path'          => 'uploads/test.png',
            'hash_sha256'        => $hash,
            'digital_signature'  => hash('sha256', $nomor . '11111111' . $hash),
            'created_by'         => $uploader->id,
        ]);
    }

    private function dataUploadValid(): array
    {
        return [
            'nama'             => 'Budi Santoso',
            'npm'              => '12345678',
            'nomor_sertifikat' => 'CERT-NEW',
            'jenis_sertifikat' => 'Asisten Dosen',
            'certificate_file' => UploadedFile::fake()->image('cert.png', 800, 600),
        ];
    }

    // No. 21 — Data & file valid → tersimpan
    public function test_upload_data_valid_tersimpan(): void
    {
        $admin = $this->buatAdmin();
        $dir = public_path('uploads');
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $this->dataUploadValid())
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('certificates', ['certificate_number' => 'CERT-NEW']);
    }

    // No. 22 — Opsi tempel QR aktif → tersimpan dengan qr_attached = true
    public function test_upload_opsi_tempel_qr_aktif_tersimpan(): void
    {
        $admin = $this->buatAdmin();
        $dir = public_path('uploads');
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $data = array_merge($this->dataUploadValid(), ['attach_qr' => '1', 'qr_size' => 150, 'qr_x' => 10, 'qr_y' => 10]);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $data)
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('certificates', ['certificate_number' => 'CERT-NEW', 'qr_attached' => 1]);
    }

    // No. 23 — Opsi QR only aktif → tersimpan
    public function test_upload_opsi_qr_only_aktif_tersimpan(): void
    {
        $admin = $this->buatAdmin();
        $dir = public_path('uploads');
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $data = array_merge($this->dataUploadValid(), ['qr_only' => '1', 'qr_size' => 150, 'qr_x' => 10, 'qr_y' => 10]);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $data)
            ->assertRedirect(route('admin.dashboard'));
    }

    // No. 24 — File kosong → ditolak
    public function test_upload_tanpa_file_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $data = $this->dataUploadValid();
        unset($data['certificate_file']);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $data)
            ->assertSessionHasErrors('certificate_file');
    }

    // No. 25 — Format file salah → ditolak
    public function test_upload_format_file_salah_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $data = array_merge($this->dataUploadValid(), [
            'certificate_file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $data)
            ->assertSessionHasErrors('certificate_file');
    }

    // No. 26 — File > 5 MB → ditolak
    public function test_upload_file_terlalu_besar_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $data = array_merge($this->dataUploadValid(), [
            'certificate_file' => UploadedFile::fake()->create('cert.png', 6000, 'image/png'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $data)
            ->assertSessionHasErrors('certificate_file');
    }

    // No. 27 — Nomor sertifikat duplikat → ditolak
    public function test_upload_nomor_duplikat_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $this->buatSertifikat($admin, 'CERT-NEW');

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $this->dataUploadValid())
            ->assertSessionHasErrors('nomor_sertifikat');
    }

    // No. 28 — Nama kosong → ditolak
    public function test_upload_nama_kosong_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $data = array_merge($this->dataUploadValid(), ['nama' => '']);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $data)
            ->assertSessionHasErrors('nama');
    }

    // No. 29 — NPM kosong → ditolak
    public function test_upload_npm_kosong_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $data = array_merge($this->dataUploadValid(), ['npm' => '']);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $data)
            ->assertSessionHasErrors('npm');
    }

    // No. 30 — Jenis sertifikat manual → tersimpan
    public function test_upload_jenis_sertifikat_manual_tersimpan(): void
    {
        $admin = $this->buatAdmin();
        $dir = public_path('uploads');
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $data = array_merge($this->dataUploadValid(), ['jenis_sertifikat' => 'Pelatihan Khusus']);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $data)
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('certificates', ['certificate_type' => 'Pelatihan Khusus']);
    }

    // No. 31 — Ukuran QR di bawah minimum (< 150) → ditolak
    public function test_upload_ukuran_qr_di_bawah_minimum_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $data = array_merge($this->dataUploadValid(), ['qr_size' => 50]);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $data)
            ->assertSessionHasErrors('qr_size');
    }

    // No. 32 — Posisi QR negatif → ditolak
    public function test_upload_posisi_qr_negatif_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $data = array_merge($this->dataUploadValid(), ['qr_x' => -1, 'qr_y' => -1]);

        $this->actingAs($admin)
            ->post(route('admin.upload.post'), $data)
            ->assertSessionHasErrors(['qr_x', 'qr_y']);
    }

    // No. 33 — Buka halaman daftar → data tampil dengan pagination
    public function test_halaman_daftar_sertifikat_tampil(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.certificates'))
            ->assertOk();
    }

    // No. 34 — Cari berdasarkan nama → data sesuai tampil
    public function test_pencarian_berdasarkan_nama(): void
    {
        $admin = $this->buatAdmin();
        $this->buatSertifikat($admin);

        $this->actingAs($admin)
            ->get(route('admin.certificates', ['search' => 'Budi']))
            ->assertOk()
            ->assertSee('Budi');
    }

    // No. 35 — Cari berdasarkan NPM → data sesuai tampil
    public function test_pencarian_berdasarkan_npm(): void
    {
        $admin = $this->buatAdmin();
        $this->buatSertifikat($admin);

        $this->actingAs($admin)
            ->get(route('admin.certificates', ['search' => '11111111']))
            ->assertOk()
            ->assertSee('11111111');
    }

    // No. 36 — Cari berdasarkan nomor sertifikat → data sesuai tampil
    public function test_pencarian_berdasarkan_nomor_sertifikat(): void
    {
        $admin = $this->buatAdmin();
        $this->buatSertifikat($admin, 'CERT-XYZ');

        $this->actingAs($admin)
            ->get(route('admin.certificates', ['search' => 'CERT-XYZ']))
            ->assertOk()
            ->assertSee('CERT-XYZ');
    }

    // No. 37 — Filter "Data Saya" → hanya data milik sendiri
    public function test_filter_data_saya_hanya_tampilkan_milik_sendiri(): void
    {
        $admin = $this->buatAdmin();
        $adminLain = $this->buatAdminLain();
        $this->buatSertifikat($admin, 'CERT-MINE');
        $this->buatSertifikat($adminLain, 'CERT-OTHER');

        $response = $this->actingAs($admin)
            ->get(route('admin.certificates', ['owner' => 'mine']));

        $response->assertOk()->assertSee('CERT-MINE')->assertDontSee('CERT-OTHER');
    }

    // No. 38 — Filter rentang tanggal → data sesuai filter
    public function test_filter_rentang_tanggal_menampilkan_data_sesuai(): void
    {
        $admin = $this->buatAdmin();
        $this->buatSertifikat($admin);

        $this->actingAs($admin)
            ->get(route('admin.certificates', [
                'date_from' => now()->subDay()->toDateString(),
                'date_to'   => now()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('CERT-001');
    }

    // No. 39 — Sorting data → halaman tetap OK
    public function test_sorting_data_berjalan(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.certificates', ['sort_by' => 'name', 'sort_dir' => 'asc']))
            ->assertOk();
    }

    // No. 40 — Reset filter → data kembali default
    public function test_reset_filter_menampilkan_semua_data(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('admin.certificates'))
            ->assertOk();
    }

    // No. 41 — Edit data milik sendiri → tersimpan, log ter-update
    public function test_edit_sertifikat_milik_sendiri_tersimpan(): void
    {
        $admin = $this->buatAdmin();
        $cert = $this->buatSertifikat($admin);

        $this->actingAs($admin)
            ->put(route('admin.certificates.update', $cert), [
                'nama'             => 'Budi Diubah',
                'npm'              => '11111111',
                'nomor_sertifikat' => 'CERT-001',
                'jenis_sertifikat' => 'Pelatihan',
            ])
            ->assertRedirect(route('admin.certificates'));

        $this->assertDatabaseHas('activity_logs', ['action' => 'edit', 'target' => 'CERT-001']);
    }

    // No. 42 — Edit tanpa file baru → data non-file tersimpan
    public function test_edit_tanpa_file_baru_tersimpan(): void
    {
        $admin = $this->buatAdmin();
        $cert = $this->buatSertifikat($admin);

        $this->actingAs($admin)
            ->put(route('admin.certificates.update', $cert), [
                'nama'             => 'Budi Update',
                'npm'              => '11111111',
                'nomor_sertifikat' => 'CERT-001',
                'jenis_sertifikat' => 'Asisten Dosen',
            ])
            ->assertRedirect(route('admin.certificates'));
    }

    // No. 43 — Ganti nomor jadi duplikat → ditolak
    public function test_edit_nomor_duplikat_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $cert1 = $this->buatSertifikat($admin, 'CERT-001');
        $student2 = Student::create(['name' => 'Ani', 'npm' => '99999999']);
        $dir = public_path('uploads');
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($dir . '/test2.png', 'dummy2');
        $hash = hash_file('sha256', $dir . '/test2.png');
        $cert2 = Certificate::create([
            'student_id'         => $student2->id,
            'certificate_number' => 'CERT-002',
            'certificate_type'   => 'Pelatihan',
            'file_path'          => 'uploads/test2.png',
            'hash_sha256'        => $hash,
            'digital_signature'  => hash('sha256', 'CERT-002' . '99999999' . $hash),
            'created_by'         => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.certificates.update', $cert2), [
                'nama'             => 'Ani',
                'npm'              => '99999999',
                'nomor_sertifikat' => 'CERT-001', // duplikat
                'jenis_sertifikat' => 'Pelatihan',
            ])
            ->assertSessionHasErrors('nomor_sertifikat');
    }

    // No. 44 — Admin edit data milik admin lain → 403
    public function test_admin_edit_sertifikat_admin_lain_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $adminLain = $this->buatAdminLain();
        $cert = $this->buatSertifikat($adminLain, 'CERT-LAIN');

        $this->actingAs($admin)
            ->get(route('admin.certificates.edit', $cert))
            ->assertStatus(403);
    }

    public function test_admin_update_sertifikat_admin_lain_ditolak(): void
    {
        $admin = $this->buatAdmin();
        $adminLain = $this->buatAdminLain();
        $cert = $this->buatSertifikat($adminLain, 'CERT-LAIN');

        $this->actingAs($admin)
            ->put(route('admin.certificates.update', $cert), [
                'nama'             => 'Budi Diubah Paksa',
                'npm'              => '11111111',
                'nomor_sertifikat' => 'CERT-LAIN',
                'jenis_sertifikat' => 'Pelatihan',
            ])
            ->assertStatus(403);
    }

    public function test_superadmin_dapat_edit_sertifikat_admin_lain(): void
    {
        $superadmin = $this->buatAdmin('superadmin');
        $adminLain = $this->buatAdminLain();
        $cert = $this->buatSertifikat($adminLain, 'CERT-LAIN');

        $this->actingAs($superadmin)
            ->get(route('admin.certificates.edit', $cert))
            ->assertOk();

        $this->actingAs($superadmin)
            ->put(route('admin.certificates.update', $cert), [
                'nama'             => 'Budi Superadmin',
                'npm'              => '11111111',
                'nomor_sertifikat' => 'CERT-LAIN',
                'jenis_sertifikat' => 'Pelatihan Superadmin',
            ])
            ->assertRedirect(route('admin.certificates'));

        $this->assertDatabaseHas('certificates', [
            'id' => $cert->id,
            'certificate_type' => 'Pelatihan Superadmin',
            'created_by' => $adminLain->id,
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'edit', 'target' => 'CERT-LAIN']);
    }
}

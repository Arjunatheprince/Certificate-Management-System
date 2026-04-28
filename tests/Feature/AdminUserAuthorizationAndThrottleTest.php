<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Certificate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminUserAuthorizationAndThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(string $role = 'admin', string $email = 'user@test.com', bool $aktif = true): User
    {
        return User::create([
            'name'      => ucfirst($role) . ' Test',
            'email'     => $email,
            'password'  => Hash::make('password123'),
            'role'      => $role,
            'is_active' => $aktif,
        ]);
    }

    private function buatSertifikat(User $uploader, string $nomor = 'CERT-DELETE'): Certificate
    {
        $student = Student::create(['name' => 'Budi Hapus', 'npm' => '123123123']);
        $suffix = str_replace('.', '', uniqid('', true));
        $certificateFilename = "delete-test-{$suffix}.png";
        $qrFilename = "qr_delete-test-{$suffix}.png";

        $uploadDir = public_path('uploads');
        $qrDir = public_path('qrcodes');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0777, true);
        }

        return Certificate::create([
            'student_id'         => $student->id,
            'certificate_number' => $nomor,
            'certificate_type'   => 'Pelatihan',
            'file_path'          => 'uploads/' . $certificateFilename,
            'hash_sha256'        => hash('sha256', 'dummy certificate'),
            'digital_signature'  => hash('sha256', $nomor . '123123123' . 'dummy certificate'),
            'qr_code'            => $qrFilename,
            'created_by'         => $uploader->id,
        ]);
    }

    // No. 45 - Superadmin hapus data -> data dan file terhapus, log tercatat.
    public function test_superadmin_dapat_menghapus_sertifikat(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');
        $certificate = $this->buatSertifikat($superadmin);

        $this->actingAs($superadmin)
            ->delete(route('admin.certificates.delete', $certificate))
            ->assertRedirect(route('admin.certificates'));

        $this->assertDatabaseMissing('certificates', ['certificate_number' => 'CERT-DELETE']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'delete', 'target' => 'CERT-DELETE']);
        $this->assertFileDoesNotExist(public_path($certificate->file_path));
        $this->assertFileDoesNotExist(public_path($certificate->qr_code_path));
    }

    // No. 46 - Admin biasa hapus data -> akses ditolak.
    public function test_admin_biasa_tidak_dapat_menghapus_sertifikat(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');
        $admin = $this->buatUser('admin', 'admin@test.com');
        $certificate = $this->buatSertifikat($superadmin);

        $this->actingAs($admin)
            ->delete(route('admin.certificates.delete', $certificate))
            ->assertStatus(403);

        $this->assertDatabaseHas('certificates', ['certificate_number' => 'CERT-DELETE']);
    }

    // No. 47 - Superadmin buka log -> semua log tampil.
    public function test_superadmin_dapat_melihat_log_aktivitas(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');
        ActivityLog::create([
            'user_id' => $superadmin->id,
            'action' => 'upload',
            'target' => 'CERT-LOG',
            'ip_address' => '127.0.0.1',
            'description' => 'Upload sertifikat uji',
        ]);

        $this->actingAs($superadmin)
            ->get(route('admin.activity-logs'))
            ->assertOk()
            ->assertSee('Upload sertifikat uji');
    }

    // No. 48 - Admin buka log -> akses ditolak.
    public function test_admin_biasa_tidak_dapat_melihat_log_aktivitas(): void
    {
        $admin = $this->buatUser('admin', 'admin@test.com');

        $this->actingAs($admin)
            ->get(route('admin.activity-logs'))
            ->assertStatus(403);
    }

    // No. 49 - Superadmin buka daftar user -> data user tampil.
    public function test_superadmin_dapat_melihat_daftar_user(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');
        $this->buatUser('admin', 'admin@test.com');

        $this->actingAs($superadmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('admin@test.com');
    }

    // No. 50 - Tambah user admin valid -> user baru tersimpan.
    public function test_superadmin_dapat_menambah_user_admin(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');

        $this->actingAs($superadmin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin Baru',
                'email' => 'adminbaru@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'adminbaru@test.com', 'role' => 'admin']);
    }

    // No. 51 - Tambah user email duplikat -> ditolak.
    public function test_tambah_user_email_duplikat_ditolak(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');
        $this->buatUser('admin', 'duplikat@test.com');

        $this->actingAs($superadmin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin Duplikat',
                'email' => 'duplikat@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('email');
    }

    // No. 52 - Password konfirmasi tidak sama -> ditolak.
    public function test_tambah_user_password_konfirmasi_tidak_sama_ditolak(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');

        $this->actingAs($superadmin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin Baru',
                'email' => 'adminbaru@test.com',
                'password' => 'password123',
                'password_confirmation' => 'beda',
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('password');
    }

    // No. 53 - Role tidak dipilih -> ditolak.
    public function test_tambah_user_tanpa_role_ditolak(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');

        $this->actingAs($superadmin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin Baru',
                'email' => 'adminbaru@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('role');
    }

    // No. 54 - Edit data user admin -> perubahan tersimpan.
    public function test_superadmin_dapat_mengubah_data_user_admin(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');
        $admin = $this->buatUser('admin', 'admin@test.com');

        $this->actingAs($superadmin)
            ->put(route('admin.users.update', $admin), [
                'name' => 'Admin Diubah',
                'email' => 'admindiubah@test.com',
                'role' => 'admin',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Admin Diubah',
            'email' => 'admindiubah@test.com',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    // No. 55 - Ubah password user -> password baru tersimpan terenkripsi.
    public function test_superadmin_dapat_mengubah_password_user(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');
        $admin = $this->buatUser('admin', 'admin@test.com');

        $this->actingAs($superadmin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
                'is_active' => '1',
                'password' => 'passwordbaru',
                'password_confirmation' => 'passwordbaru',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('passwordbaru', $admin->fresh()->password));
    }

    // No. 56 - Superadmin turunkan role sendiri -> role tetap superadmin.
    public function test_superadmin_tidak_dapat_menurunkan_role_sendiri(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');

        $this->actingAs($superadmin)
            ->put(route('admin.users.update', $superadmin), [
                'name' => $superadmin->name,
                'email' => $superadmin->email,
                'role' => 'admin',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('superadmin', $superadmin->fresh()->role);
    }

    // No. 57 - Hapus akun sendiri -> ditolak.
    public function test_superadmin_tidak_dapat_menghapus_akun_sendiri(): void
    {
        $superadmin = $this->buatUser('superadmin', 'superadmin@test.com');

        $this->actingAs($superadmin)
            ->delete(route('admin.users.destroy', $superadmin))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('users', ['id' => $superadmin->id]);
    }

    // No. 58 - Admin buka manajemen user -> akses ditolak.
    public function test_admin_biasa_tidak_dapat_mengakses_manajemen_user(): void
    {
        $admin = $this->buatUser('admin', 'admin@test.com');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    // No. 59 - Guest buka upload sertifikat -> redirect ke login.
    public function test_guest_akses_upload_sertifikat_diarahkan_ke_login(): void
    {
        $this->get(route('admin.upload'))
            ->assertRedirect(route('login'));
    }

    // No. 60 - Request login/verifikasi berulang -> throttle aktif.
    public function test_throttle_aktif_pada_login_dan_verifikasi(): void
    {
        RateLimiter::clear('127.0.0.1|throttle@test.com');
        RateLimiter::clear('127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'throttle@test.com',
                'password' => 'password-salah',
            ])->assertStatus(302);
        }

        $this->post('/login', [
            'email' => 'throttle@test.com',
            'password' => 'password-salah',
        ])->assertStatus(429);

        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/verify', ['cert_number' => 'CERT-TIDAK-ADA'])
                ->assertOk();
        }

        $this->postJson('/verify', ['cert_number' => 'CERT-TIDAK-ADA'])
            ->assertStatus(429);
    }
}

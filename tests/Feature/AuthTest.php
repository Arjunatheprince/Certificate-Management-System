<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(string $role = 'admin', bool $aktif = true): User
    {
        return User::create([
            'name'      => 'Test User',
            'email'     => 'user@test.com',
            'password'  => Hash::make('password123'),
            'role'      => $role,
            'is_active' => $aktif,
        ]);
    }

    // No. 14 — Email & password benar → redirect ke dashboard
    public function test_login_kredensial_benar_redirect_ke_dashboard(): void
    {
        $this->buatUser();

        $this->post('/login', ['email' => 'user@test.com', 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));
    }

    // No. 15 — Password salah → login ditolak
    public function test_login_password_salah_ditolak(): void
    {
        $this->buatUser();

        $this->post('/login', ['email' => 'user@test.com', 'password' => 'salah'])
            ->assertSessionHasErrors('email');
    }

    // No. 16 — Email tidak terdaftar → login ditolak
    public function test_login_email_tidak_terdaftar_ditolak(): void
    {
        $this->post('/login', ['email' => 'tidakada@test.com', 'password' => 'password123'])
            ->assertSessionHasErrors('email');
    }

    // No. 17 — Akun nonaktif → login ditolak
    public function test_login_akun_nonaktif_ditolak(): void
    {
        $this->buatUser('admin', false);

        $this->post('/login', ['email' => 'user@test.com', 'password' => 'password123'])
            ->assertSessionHasErrors('email');
    }

    // No. 18 — Field kosong → validasi tampil
    public function test_login_field_kosong_validasi_tampil(): void
    {
        $this->post('/login', [])
            ->assertSessionHasErrors(['email', 'password']);
    }

    // No. 19 — Logout → session dihapus, redirect ke home
    public function test_logout_menghapus_session_dan_redirect_ke_home(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    // No. 20 — Guest buka /admin → redirect ke login
    public function test_guest_akses_dashboard_diarahkan_ke_login(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('login'));
    }
}

# Lampiran Kode Program Ringkas

Lampiran ini menampilkan potongan kode utama dari Sistem Autentikasi Sertifikat Digital. Kode dipilih dari bagian yang mewakili fungsi inti sistem: autentikasi, verifikasi sertifikat, upload sertifikat, QR Code, hash SHA-256, role-based access control, header keamanan, log aktivitas, dan pengujian.

Kode lengkap proyek disertakan secara digital pada file `lampiran_kode_program.md` atau folder proyek. File dependency, cache, build, upload, QR hasil generate, database lokal, dan file `.env` tidak dicantumkan karena bukan kode program inti atau dapat memuat konfigurasi sensitif.

## A. Struktur Direktori Utama

```text
certificate-system/
|-- app/
|   |-- Http/Controllers/
|   |-- Http/Middleware/
|   |-- Models/
|   `-- Providers/
|-- database/migrations/
|-- resources/views/
|-- routes/web.php
|-- tests/Feature/
|-- test_blackbox_testing.php
|-- test_security_testing.php
`-- test_owasp_security.php
```

## B. Daftar File Penting

| No | File | Fungsi |
|---:|---|---|
| 1 | `routes/web.php` | Route publik, admin, superadmin, dan middleware |
| 2 | `AuthController.php` | Login, logout, validasi akun, dan log aktivitas |
| 3 | `CertificateController.php` | Verifikasi manual, QR, dan integritas file |
| 4 | `AdminController.php` | Upload, daftar, edit, hapus, QR, hash, dan sertifikat |
| 5 | `UserController.php` | Manajemen user oleh superadmin |
| 6 | `RoleMiddleware.php` | Pembatasan akses berdasarkan role |
| 7 | `ContentSecurityPolicy.php` | Header keamanan HTTP |
| 8 | `User.php`, `Certificate.php`, `ActivityLog.php` | Model utama sistem |
| 9 | `tests/Feature/*.php` | Pengujian fitur sistem |
| 10 | `test_blackbox_testing.php`, `test_security_testing.php`, `test_owasp_security.php` | Rekap pengujian |

## C. Potongan Kode Program Utama

### 1. Route Sistem

File: `routes/web.php`

```php
Route::get('/', [CertificateController::class, 'index'])->name('home');
Route::post('/verify', [CertificateController::class, 'verify'])
    ->middleware('throttle:verify')
    ->name('verify');
Route::post('/verify-qr', [CertificateController::class, 'verifyQr'])
    ->middleware('throttle:verify-qr')
    ->name('verify.qr');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
```

Route publik digunakan untuk verifikasi sertifikat, verifikasi QR, login, dan logout. Middleware `throttle` membatasi request berulang.

```php
Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/certificates', [AdminController::class, 'list'])->name('admin.certificates');
    Route::get('/admin/upload', [AdminController::class, 'showUpload'])->name('admin.upload');
    Route::post('/admin/upload', [AdminController::class, 'upload'])->name('admin.upload.post');
    Route::get('/admin/certificates/{certificate}/edit', [AdminController::class, 'edit'])->name('admin.certificates.edit');
    Route::put('/admin/certificates/{certificate}', [AdminController::class, 'update'])->name('admin.certificates.update');
});

Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/admin/activity-logs', [AdminController::class, 'activityLogs'])->name('admin.activity-logs');
    Route::delete('/admin/certificates/{certificate}', [AdminController::class, 'delete'])->name('admin.certificates.delete');
    Route::resource('/admin/users', UserController::class)->names('admin.users');
});
```

Route admin dan superadmin dipisahkan sesuai hak akses. Fitur hapus sertifikat, log aktivitas, dan manajemen user hanya tersedia untuk superadmin.

### 2. Autentikasi User

File: `app/Http/Controllers/AuthController.php`

```php
public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return back()->withErrors(['email' => 'Email atau password salah']);
    }

    if (!$user->is_active) {
        return back()->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi Super Admin.']);
    }

    Auth::login($user);
    $request->session()->regenerate();
    ActivityLog::record('login', $user->email, "Login sebagai {$user->role_label}");

    return redirect()->route('admin.dashboard');
}
```

Kode login memvalidasi input, memeriksa password dengan `Hash::check`, memastikan akun aktif, meregenerasi session, dan mencatat aktivitas login.

```php
public function logout(Request $request)
{
    ActivityLog::record('logout', Auth::user()?->email);
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
}
```

### 3. Verifikasi Sertifikat dan Integritas File

File: `app/Http/Controllers/CertificateController.php`

```php
public function verify(Request $request)
{
    $request->validate(['cert_number' => 'required|string|max:100']);

    $cert = Certificate::with('student')
        ->where('certificate_number', $request->cert_number)
        ->first();

    if (!$cert) {
        return response()->json(['success' => false, 'message' => 'Sertifikat tidak ditemukan']);
    }

    if (!$this->verifyFileIntegrity($cert)) {
        return response()->json([
            'success' => false,
            'message' => 'Integritas file sertifikat tidak valid. File mungkin telah dimodifikasi.',
        ]);
    }

    return response()->json([
        'success' => true,
        'data' => $this->serializePublicCertificate($cert),
    ]);
}
```

```php
private function verifyFileIntegrity(Certificate $cert): bool
{
    if (!$cert->hash_sha256 || !$cert->file_path) {
        return true;
    }

    $filePath = public_path($cert->file_path);

    if (!file_exists($filePath)) {
        return false;
    }

    return hash_file('sha256', $filePath) === $cert->hash_sha256;
}
```

Sistem mencari sertifikat berdasarkan nomor sertifikat, lalu membandingkan hash SHA-256 file saat ini dengan hash yang tersimpan di database.

### 4. Verifikasi QR Code

File: `app/Http/Controllers/CertificateController.php`

```php
public function verifyQr(Request $request)
{
    $request->validate([
        'qr_image' => 'required|file|mimes:jpg,jpeg,png|max:5120',
    ]);

    $file = $request->file('qr_image');
    $text = $this->readQrWithFallback($file->getPathname());

    if (!$text) {
        return response()->json(['success' => false, 'message' => 'QR code tidak dapat dibaca.']);
    }

    if (preg_match('/nomor_sertifikat=([^&]+)/', $text, $matches)) {
        $certNumber = urldecode($matches[1]);
        $cert = Certificate::with('student')
            ->where('certificate_number', $certNumber)
            ->first();

        if (!$cert || !$this->verifyFileIntegrity($cert)) {
            return response()->json(['success' => false, 'message' => 'Sertifikat tidak valid']);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializePublicCertificate($cert),
        ]);
    }

    return response()->json(['success' => false, 'message' => 'Format QR code tidak valid']);
}
```

QR Code dibaca dari file gambar. Jika QR memuat parameter `nomor_sertifikat`, sistem melakukan pencarian dan validasi integritas sertifikat.

### 5. Upload Sertifikat

File: `app/Http/Controllers/AdminController.php`

```php
public function upload(Request $request)
{
    $validated = $this->validateCertificateRequest($request);

    DB::transaction(function () use ($request, $validated) {
        $filePath = $this->storeUploadedFile($request->file('certificate_file'));
        $qrConfig = $this->extractQrConfig($request);
        $qrFile = $this->generateQrCode($validated['nomor_sertifikat'], false);

        $this->applyQrToCertificate($filePath, $qrFile['relative_path'], $qrConfig);

        $student = $this->syncStudent($validated['npm'], $validated['nama']);
        $security = $this->generateSecurityMetadata(
            $validated['nomor_sertifikat'],
            $validated['npm'],
            $filePath
        );

        Certificate::create([
            'student_id'         => $student->id,
            'certificate_number' => $validated['nomor_sertifikat'],
            'certificate_type'   => $validated['jenis_sertifikat'],
            'file_path'          => $filePath,
            'hash_sha256'        => $security['hash_sha256'],
            'digital_signature'  => $security['digital_signature'],
            'qr_code'            => $qrFile['filename'],
            'created_by'         => Auth::id(),
        ]);

        ActivityLog::record('upload', $validated['nomor_sertifikat'], "Upload sertifikat atas nama {$validated['nama']}");
    });

    return redirect()->route('admin.dashboard')->with('success', 'Sertifikat berhasil diupload');
}
```

Proses upload dilakukan dalam `DB::transaction` agar penyimpanan file, QR, hash, digital signature, data sertifikat, dan log aktivitas berjalan konsisten.

### 6. Validasi Input dan Metadata Keamanan

File: `app/Http/Controllers/AdminController.php`

```php
private function validateCertificateRequest(Request $request, ?Certificate $certificate = null): array
{
    return $request->validate([
        'nama'             => 'required|string|max:150',
        'npm'              => 'required|string|max:50',
        'nomor_sertifikat' => 'required|string|max:100|unique:certificates,certificate_number,' . $certificate?->id,
        'jenis_sertifikat' => 'required|string|max:100',
        'valid_until'      => 'nullable|date',
        'certificate_file' => ($certificate ? 'nullable' : 'required') . '|mimes:jpg,jpeg,png|max:5120',
        'qr_size'          => 'nullable|integer|min:150|max:400',
        'qr_x'             => 'nullable|integer|min:0|max:5000',
        'qr_y'             => 'nullable|integer|min:0|max:5000',
    ]);
}
```

```php
private function generateQrCode(string $certificateNumber, bool $addTimestamp): array
{
    $filename = 'qr_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $certificateNumber) . '.png';
    $relativePath = 'qrcodes/' . $filename;

    $result = (new PngWriter())->write(
        new QrCode(url('/verify?nomor_sertifikat=' . $certificateNumber))
    );
    $result->saveToFile(public_path($relativePath));

    return ['filename' => $filename, 'relative_path' => $relativePath];
}

private function generateSecurityMetadata(string $certificateNumber, string $npm, string $filePath): array
{
    $hash = hash_file('sha256', public_path($filePath));

    return [
        'hash_sha256'       => $hash,
        'digital_signature' => hash('sha256', $certificateNumber . $npm . $hash),
    ];
}
```

Validasi membatasi format input dan file upload. QR Code berisi URL verifikasi, sedangkan metadata keamanan dibuat dari hash SHA-256 dan digital signature.

### 7. Pembatasan Akses

File: `app/Http/Controllers/AdminController.php`

```php
private function authorizeCertificateAccess(Certificate $certificate): void
{
    $user = $this->currentUser();

    if (!$user->isSuperAdmin() && !$user->isAdmin()) {
        abort(403, 'Anda tidak memiliki akses untuk mengelola sertifikat ini.');
    }

    if ($user->isAdmin() && (int) $certificate->created_by !== (int) $user->id) {
        abort(403, 'Anda tidak memiliki akses untuk mengelola sertifikat ini.');
    }
}
```

Admin hanya dapat mengelola sertifikat miliknya sendiri, sedangkan superadmin memiliki akses lebih luas.

File: `app/Http/Middleware/RoleMiddleware.php`

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    if (!$user->is_active) {
        auth()->logout();
        return redirect()->route('login')->withErrors(['email' => 'Akun Anda telah dinonaktifkan.']);
    }

    if (!in_array($user->role, $roles)) {
        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    return $next($request);
}
```

### 8. Header Keamanan HTTP

File: `app/Http/Middleware/ContentSecurityPolicy.php`

```php
$csp = implode('; ', [
    "default-src 'self'",
    "script-src 'self' 'nonce-{$nonce}'",
    "style-src 'self' 'unsafe-inline'",
    "font-src 'self'",
    "img-src 'self' data:",
    "connect-src 'self'",
    "frame-ancestors 'none'",
    "object-src 'none'",
    "base-uri 'self'",
    "form-action 'self'",
]);

$response->headers->set('Content-Security-Policy', $csp);
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('X-Frame-Options', 'DENY');
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
```

Header keamanan digunakan untuk mengurangi risiko XSS, clickjacking, dan akses resource dari luar domain.

### 9. Model Utama

File: `app/Models/User.php`, `app/Models/Certificate.php`, `app/Models/ActivityLog.php`

```php
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];
    protected $hidden = ['password'];

    public function isSuperAdmin(): bool { return $this->role === 'superadmin'; }
    public function isAdmin(): bool      { return $this->role === 'admin'; }
}

class Certificate extends Model
{
    protected $fillable = [
        'student_id', 'certificate_number', 'certificate_type',
        'file_path', 'hash_sha256', 'digital_signature',
        'qr_code', 'qr_attached', 'qr_x', 'qr_y', 'qr_size', 'created_by',
    ];

    public function student()  { return $this->belongsTo(Student::class); }
    public function uploader() { return $this->belongsTo(User::class, 'created_by'); }
}

public static function record(string $action, ?string $target = null, ?string $description = null): void
{
    static::create([
        'user_id' => auth()->id(),
        'action' => $action,
        'target' => $target,
        'ip_address' => request()->ip(),
        'description' => $description,
    ]);
}
```

Model utama digunakan untuk data user, data sertifikat, relasi mahasiswa, user pengunggah, dan pencatatan aktivitas sistem.

## D. Potongan Kode Pengujian

### 1. Verifikasi Manual

File: `tests/Feature/VerifikasiManualTest.php`

```php
public function test_nomor_valid_menampilkan_data_sertifikat(): void
{
    $this->buatSertifikat('CERT-001');

    $this->postJson('/verify', ['cert_number' => 'CERT-001'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => [
            'nama', 'npm', 'nomor_sertifikat', 'jenis_sertifikat',
            'tanggal_terbit', 'file_path',
        ]]);
}
```

### 2. Login dan Upload Sertifikat

File: `tests/Feature/AuthTest.php` dan `tests/Feature/SertifikatTest.php`

```php
public function test_login_kredensial_benar_redirect_ke_dashboard(): void
{
    $this->buatUser();

    $this->post('/login', [
        'email' => 'user@test.com',
        'password' => 'password123',
    ])->assertRedirect(route('admin.dashboard'));
}

public function test_upload_data_valid_tersimpan(): void
{
    $admin = $this->buatAdmin();

    $this->actingAs($admin)
        ->post(route('admin.upload.post'), $this->dataUploadValid())
        ->assertRedirect(route('admin.dashboard'));

    $this->assertDatabaseHas('certificates', [
        'certificate_number' => 'CERT-NEW',
    ]);
}
```

### 3. Otorisasi, Throttle, dan Manipulasi Session

File: `tests/Feature/AdminUserAuthorizationAndThrottleTest.php` dan `tests/Feature/SessionRoleManipulationTest.php`

```php
public function test_admin_biasa_tidak_dapat_mengakses_manajemen_user(): void
{
    $admin = $this->buatUser('admin', 'admin@test.com');

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertStatus(403);
}

public function test_throttle_aktif_pada_login_dan_verifikasi(): void
{
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
}

public function test_admin_route_rejects_manual_session_role_escalation(): void
{
    $this->actingAs($user)
        ->withSession(['role' => 'superadmin'])
        ->get(route('admin.users.index'))
        ->assertStatus(403);
}
```

## E. Rekapitulasi Pengujian

File: `test_blackbox_testing.php`

```php
$modules = [
    ['no' => 1, 'modul' => 'Verifikasi Manual',              'skenario' => '1-5'],
    ['no' => 2, 'modul' => 'Verifikasi QR Upload',           'skenario' => '6-10'],
    ['no' => 3, 'modul' => 'Scan QR Kamera',                 'skenario' => '11-13'],
    ['no' => 4, 'modul' => 'Login, Logout, Akses Dashboard', 'skenario' => '14-20'],
    ['no' => 5, 'modul' => 'Upload Sertifikat',              'skenario' => '21-32'],
    ['no' => 6, 'modul' => 'Daftar Sertifikat',              'skenario' => '33-40'],
    ['no' => 7, 'modul' => 'Edit Sertifikat',                'skenario' => '41-44'],
    ['no' => 8, 'modul' => 'Hapus, Log, User, Akses',        'skenario' => '45-60'],
];
```

File: `test_security_testing.php`

```php
$total   = count($results);
$success = count(array_filter($results, fn($r) => $r['passed']));
$failed  = $total - $success;
$percent = round(($success / $total) * 100, 1);

$statusLabel = $percent === 100.0
    ? 'SANGAT AMAN'
    : ($percent >= 75 ? 'AMAN' : 'PERLU PERBAIKAN');
```

File: `test_owasp_security.php`

```php
$protectedUrls = [
    '/admin'               => 'Dashboard Admin',
    '/admin/upload'        => 'Upload Sertifikat',
    '/admin/certificates'  => 'Daftar Sertifikat',
    '/admin/users'         => 'Kelola User',
    '/admin/activity-logs' => 'Activity Log',
];

foreach ($protectedUrls as $path => $label) {
    $res = httpGet("{$baseUrl}{$path}");
    $passed = in_array($res['code'], [302, 403]);
    $results[] = [
        'owasp' => 'A01',
        'skenario' => "Akses {$path} tanpa autentikasi",
        'passed' => $passed,
    ];
}
```

Rekapitulasi pengujian digunakan untuk menyajikan hasil black box testing, security testing, dan pengujian berdasarkan OWASP Top 10 2021.

## F. Keterangan

Lampiran ini merupakan versi ringkas untuk dokumen cetak/PDF skripsi. Kode lengkap tetap disertakan dalam bentuk digital melalui `lampiran_kode_program.md` atau folder proyek.

File berikut tidak dimasukkan ke lampiran cetak:

```text
vendor/
node_modules/
storage/
public/uploads/
public/qrcodes/
bootstrap/cache/
.env
database lokal
file hasil build
```

File tersebut tidak dicantumkan karena merupakan dependency, cache, file hasil generate, konfigurasi sensitif, atau data runtime.

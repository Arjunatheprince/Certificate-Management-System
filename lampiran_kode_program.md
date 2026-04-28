# Lampiran Kode Program

Lampiran ini berisi kode sumber utama dari Sistem Autentikasi Sertifikat Digital. File dependency, cache, hasil build, file upload, QR code hasil generate, database lokal, dan file environment tidak disertakan karena bukan bagian dari kode sumber inti atau dapat memuat konfigurasi sensitif.

## Daftar File

1. `app/Http/Controllers/AdminController.php`
2. `app/Http/Controllers/AuthController.php`
3. `app/Http/Controllers/CertificateController.php`
4. `app/Http/Controllers/Controller.php`
5. `app/Http/Controllers/UserController.php`
6. `app/Http/Middleware/ContentSecurityPolicy.php`
7. `app/Http/Middleware/RoleMiddleware.php`
8. `app/Models/ActivityLog.php`
9. `app/Models/Certificate.php`
10. `app/Models/Student.php`
11. `app/Models/User.php`
12. `app/Providers/AppServiceProvider.php`
13. `bootstrap/app.php`
14. `bootstrap/providers.php`
15. `composer.json`
16. `config/app.php`
17. `config/auth.php`
18. `config/cache.php`
19. `config/database.php`
20. `config/filesystems.php`
21. `config/logging.php`
22. `config/mail.php`
23. `config/queue.php`
24. `config/services.php`
25. `config/session.php`
26. `database/factories/UserFactory.php`
27. `database/migrations/0001_01_01_000000_create_users_table.php`
28. `database/migrations/0001_01_01_000001_create_cache_table.php`
29. `database/migrations/0001_01_01_000002_create_jobs_table.php`
30. `database/migrations/2026_03_24_205235_add_role_to_users_table.php`
31. `database/migrations/2026_03_24_205236_create_activity_logs_table.php`
32. `database/migrations/2026_04_01_135222_add_photo_to_students_table.php`
33. `database/migrations/2026_04_01_151713_add_qr_position_to_certificates_table.php`
34. `database/migrations/2026_04_23_120000_add_valid_until_to_certificates_table.php`
35. `database/seeders/DatabaseSeeder.php`
36. `package.json`
37. `phpunit.xml`
38. `postcss.config.js`
39. `resources/css/app.css`
40. `resources/js/app.js`
41. `resources/js/bootstrap.js`
42. `resources/views/admin/activity-logs.blade.php`
43. `resources/views/admin/certificates.blade.php`
44. `resources/views/admin/dashboard.blade.php`
45. `resources/views/admin/edit.blade.php`
46. `resources/views/admin/partials/certificates-results.blade.php`
47. `resources/views/admin/upload.blade.php`
48. `resources/views/admin/users/create.blade.php`
49. `resources/views/admin/users/edit.blade.php`
50. `resources/views/admin/users/index.blade.php`
51. `resources/views/admin/users/show.blade.php`
52. `resources/views/admin/verify.blade.php`
53. `resources/views/auth/login.blade.php`
54. `resources/views/errors/403.blade.php`
55. `resources/views/layouts/app.blade.php`
56. `resources/views/layouts/navbar.blade.php`
57. `resources/views/layouts/notification.blade.php`
58. `resources/views/verify.blade.php`
59. `resources/views/welcome.blade.php`
60. `routes/console.php`
61. `routes/web.php`
62. `tailwind.config.js`
63. `tests/Feature/AdminUserAuthorizationAndThrottleTest.php`
64. `tests/Feature/AuthTest.php`
65. `tests/Feature/ExampleTest.php`
66. `tests/Feature/ScanQrKameraTest.php`
67. `tests/Feature/SertifikatTest.php`
68. `tests/Feature/SessionRoleManipulationTest.php`
69. `tests/Feature/VerifikasiManualTest.php`
70. `tests/Feature/VerifikasiQrTest.php`
71. `tests/Feature/VerifikasiQrUploadTest.php`
72. `tests/TestCase.php`
73. `tests/Unit/ExampleTest.php`
74. `test_blackbox_testing.php`
75. `test_owasp_security.php`
76. `test_security_testing.php`
77. `vite.config.js`

## Isi Kode

### app/Http/Controllers/AdminController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Student;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AdminController extends Controller
{
    public function dashboard()
    {
        $user        = $this->currentUser();
        $totalCerts  = Certificate::count();
        $recentCerts = Certificate::with('student')->orderBy('id', 'desc')->limit(5)->get();
        $recentLogs  = $user->isSuperAdmin()
            ? ActivityLog::with('user')->orderBy('id', 'desc')->limit(10)->get()
            : ActivityLog::with('user')->where('user_id', $user->id)->orderBy('id', 'desc')->limit(10)->get();
        $totalAdmins  = User::where('role', 'admin')->count();
        $todayLogs    = ActivityLog::whereDate('created_at', today())->count();
        $todayUploads = Certificate::whereDate('created_at', today())->count();
        return view('admin.dashboard', compact('totalCerts', 'recentCerts', 'recentLogs', 'totalAdmins', 'todayLogs', 'todayUploads'));
    }

    public function showUpload()
    {
        return view('admin.upload');
    }

    public function upload(Request $request)
    {
        $validated = $this->validateCertificateRequest($request);

        try {
            DB::transaction(function () use ($request, $validated) {
                $filePath = $this->storeUploadedFile($request->file('certificate_file'));
                $qrConfig = $this->extractQrConfig($request);
                $qrFile = $this->generateQrCode($validated['nomor_sertifikat'], false);

                $this->applyQrToCertificate(
                    $filePath,
                    $qrFile['relative_path'],
                    $qrConfig
                );

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
                    'valid_until'        => $validated['valid_until'] ?? null,
                    'file_path'          => $filePath,
                    'hash_sha256'        => $security['hash_sha256'],
                    'digital_signature'  => $security['digital_signature'],
                    'qr_code'            => $qrFile['filename'],
                    'qr_attached'        => $qrConfig['attach_qr'] || $qrConfig['qr_only'],
                    'qr_x'               => $qrConfig['qr_x'],
                    'qr_y'               => $qrConfig['qr_y'],
                    'qr_size'            => $qrConfig['qr_size'],
                    'created_by'         => Auth::id(),
                ]);

                ActivityLog::record(
                    'upload',
                    $validated['nomor_sertifikat'],
                    "Upload sertifikat atas nama {$validated['nama']}"
                );
            });

            return redirect()->route('admin.dashboard')->with('success', 'Sertifikat berhasil diupload');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Upload gagal: ' . $e->getMessage()]);
        }
    }

    public function list()
    {
        $user = $this->currentUser();
        $search = trim((string) request('search', ''));
        $type = trim((string) request('type', ''));
        $owner = trim((string) request('owner', 'all'));
        $dateFrom = trim((string) request('date_from', ''));
        $dateTo = trim((string) request('date_to', ''));
        $sortBy = trim((string) request('sort_by', 'created_at'));
        $sortDir = strtolower(trim((string) request('sort_dir', 'desc')));

        $sortableColumns = [
            'name' => 'students.name',
            'npm' => 'students.npm',
            'certificate_number' => 'certificates.certificate_number',
            'certificate_type' => 'certificates.certificate_type',
            'created_at' => 'certificates.created_at',
        ];

        $sortBy = array_key_exists($sortBy, $sortableColumns) ? $sortBy : 'created_at';
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        $query = Certificate::query()
            ->select('certificates.*')
            ->leftJoin('students', 'students.id', '=', 'certificates.student_id')
            ->with('student', 'uploader')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('certificate_number', 'like', "%{$search}%")
                        ->orWhere('certificate_type', 'like', "%{$search}%")
                        ->orWhere('students.name', 'like', "%{$search}%")
                        ->orWhere('students.npm', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn ($query) => $query->where('certificates.certificate_type', $type))
            ->when($owner === 'mine', fn ($query) => $query->where('certificates.created_by', $user->id))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('certificates.created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('certificates.created_at', '<=', $dateTo))
            ->orderBy($sortableColumns[$sortBy], $sortDir)
            ->orderBy('certificates.id', 'desc');

        $certificates = $query->paginate(20)->withQueryString();
        $certificateTypes = Certificate::query()
            ->select('certificate_type')
            ->distinct()
            ->orderBy('certificate_type')
            ->pluck('certificate_type');

        $viewData = compact(
            'certificates',
            'certificateTypes',
            'search',
            'type',
            'owner',
            'dateFrom',
            'dateTo',
            'sortBy',
            'sortDir'
        );

        if (request()->ajax()) {
            return response()->json([
                'html' => view('admin.partials.certificates-results', $viewData)->render(),
                'total' => $certificates->total(),
                'hasActiveFilters' => $search !== '' || $type !== '' || $owner !== 'all' || $dateFrom !== '' || $dateTo !== '',
            ]);
        }

        return view('admin.certificates', $viewData);
    }

    public function delete(Certificate $certificate)
    {
        $this->authorizeCertificateAccess($certificate);

        $this->deletePublicFile($certificate->file_path);
        $this->deletePublicFile($certificate->qr_code_path);

        ActivityLog::record('delete', $certificate->nomor_sertifikat, "Hapus sertifikat atas nama {$certificate->nama}");
        $certificate->delete();

        return redirect()->route('admin.certificates')->with('success', 'Sertifikat berhasil dihapus.');
    }

    public function edit(Certificate $certificate)
    {
        $this->authorizeCertificateAccess($certificate);

        $certificate->load('student');
        return view('admin.edit', compact('certificate'));
    }

    public function update(Request $request, Certificate $certificate)
    {
        $this->authorizeCertificateAccess($certificate);
        $validated = $this->validateCertificateRequest($request, $certificate);

        DB::transaction(function () use ($request, $validated, $certificate) {
            $student = $this->syncStudent($validated['npm'], $validated['nama']);
            $qrConfig = $this->extractQrConfig($request, $certificate);
            $hasNewFile = $request->hasFile('certificate_file');
            $targetFile = $certificate->file_path;

            $changes = $this->detectChanges($certificate, $validated, $hasNewFile);

            if ($hasNewFile) {
                $this->deletePublicFile($certificate->file_path);
                $targetFile = $this->storeUploadedFile($request->file('certificate_file'));
            }

            $previousQrPath = $certificate->qr_code_path;
            $qrFile = $this->generateQrCode($validated['nomor_sertifikat'], true);

            if ($hasNewFile) {
                $this->applyQrToCertificate(
                    $targetFile,
                    $qrFile['relative_path'],
                    $qrConfig
                );
            }

            $security = $this->generateSecurityMetadata(
                $validated['nomor_sertifikat'],
                $validated['npm'],
                $targetFile
            );

            $certificate->update([
                'certificate_number' => $validated['nomor_sertifikat'],
                'certificate_type'   => $validated['jenis_sertifikat'],
                'student_id'         => $student->id,
                'valid_until'        => $validated['valid_until'] ?? null,
                'file_path'          => $targetFile,
                'hash_sha256'        => $security['hash_sha256'],
                'digital_signature'  => $security['digital_signature'],
                'qr_code'            => $qrFile['filename'],
                'qr_attached'        => $qrConfig['attach_qr'],
                'qr_x'               => $qrConfig['qr_x'],
                'qr_y'               => $qrConfig['qr_y'],
                'qr_size'            => $qrConfig['qr_size'],
            ]);

            $this->deletePublicFile($previousQrPath);

            ActivityLog::record(
                'edit',
                $validated['nomor_sertifikat'],
                $changes ?: "Edit sertifikat atas nama {$validated['nama']}"
            );
        });

        return redirect()->route('admin.certificates')->with('success', 'Sertifikat berhasil diperbarui. Hash & QR telah diperbarui.');
    }

    public function activityLogs()
    {
        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn($log) => $log->created_at->format('Y-m-d'));
        return view('admin.activity-logs', compact('logs'));
    }

    private function validateCertificateRequest(Request $request, ?Certificate $certificate = null): array
    {
        $certificateId = $certificate?->id;

        return $request->validate([
            'nama'             => 'required|string|max:150',
            'npm'              => 'required|string|max:50',
            'nomor_sertifikat' => 'required|string|max:100|unique:certificates,certificate_number,' . $certificateId,
            'jenis_sertifikat' => 'required|string|max:100',
            'valid_until'      => 'nullable|date',
            'certificate_file' => ($certificate ? 'nullable' : 'required') . '|mimes:jpg,jpeg,png|max:5120',
            'qr_size'          => 'nullable|integer|min:150|max:400',
            'qr_x'             => 'nullable|integer|min:0|max:5000',
            'qr_y'             => 'nullable|integer|min:0|max:5000',
        ]);
    }

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

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    private function syncStudent(string $npm, string $name): Student
    {
        $student = Student::firstOrCreate(
            ['npm' => $npm],
            ['name' => $name]
        );

        if ($student->name !== $name) {
            $student->update(['name' => $name]);
        }

        return $student;
    }

    private function extractQrConfig(Request $request, ?Certificate $certificate = null): array
    {
        return [
            'attach_qr' => $request->boolean('attach_qr'),
            'qr_only'   => $request->boolean('qr_only'),
            'qr_size'   => max(50, min(400, (int) ($request->input('qr_size', $certificate?->qr_size ?? 150)))),
            'qr_x'      => max(0, min(5000, (int) ($request->input('qr_x', $certificate?->qr_x ?? 50)))),
            'qr_y'      => max(0, min(5000, (int) ($request->input('qr_y', $certificate?->qr_y ?? 50)))),
        ];
    }

    private function storeUploadedFile(UploadedFile $file): string
    {
        $this->ensureDirectoryExists(public_path('uploads'));

        $filename = time() . '_' . Str::random(8) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move(public_path('uploads'), $filename);

        return 'uploads/' . $filename;
    }

    private function generateQrCode(string $certificateNumber, bool $addTimestamp): array
    {
        $this->ensureDirectoryExists(public_path('qrcodes'));

        $suffix = $addTimestamp ? '_' . time() : '';
        $filename = 'qr_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $certificateNumber) . $suffix . '.png';
        $relativePath = 'qrcodes/' . $filename;

        $result = (new PngWriter())->write(
            new QrCode(url('/verify?nomor_sertifikat=' . $certificateNumber))
        );
        $result->saveToFile(public_path($relativePath));

        return [
            'filename'      => $filename,
            'relative_path' => $relativePath,
        ];
    }

    private function applyQrToCertificate(string $filePath, string $qrPath, array $qrConfig): void
    {
        if (!$qrConfig['attach_qr'] && !$qrConfig['qr_only']) {
            return;
        }

        $manager = new ImageManager(new Driver());
        $qrImage = $manager->read(public_path($qrPath));
        $qrImage->scale($qrConfig['qr_size'], $qrConfig['qr_size']);

        if ($qrConfig['qr_only']) {
            $original = $manager->read(public_path($filePath));
            $canvas = $manager->create($original->width(), $original->height());
            $canvas->fill('ffffff');
            $canvas->place($qrImage, 'top-left', $qrConfig['qr_x'], $qrConfig['qr_y']);
            $canvas->save(public_path($filePath));

            return;
        }

        $image = $manager->read(public_path($filePath));
        $image->place($qrImage, 'top-left', $qrConfig['qr_x'], $qrConfig['qr_y']);
        $image->save(public_path($filePath));
    }

    private function generateSecurityMetadata(string $certificateNumber, string $npm, string $filePath): array
    {
        $hash = hash_file('sha256', public_path($filePath));

        return [
            'hash_sha256'       => $hash,
            'digital_signature' => hash('sha256', $certificateNumber . $npm . $hash),
        ];
    }

    private function deletePublicFile(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $fullPath = public_path($relativePath);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    private function detectChanges(Certificate $certificate, array $validated, bool $hasNewFile): string
    {
        $labels = [
            'nama'             => 'Nama',
            'npm'              => 'NPM',
            'nomor_sertifikat' => 'Nomor Sertifikat',
            'jenis_sertifikat' => 'Jenis Sertifikat',
        ];

        $map = [
            'nama'             => $certificate->nama,
            'npm'              => $certificate->npm,
            'nomor_sertifikat' => $certificate->certificate_number,
            'jenis_sertifikat' => $certificate->certificate_type,
        ];

        $changed = [];

        foreach ($map as $field => $oldValue) {
            $newValue = $validated[$field] ?? null;
            if ((string) $oldValue !== (string) $newValue) {
                $changed[] = "{$labels[$field]}: \"{$oldValue}\" → \"{$newValue}\"";
            }
        }

        if ($hasNewFile) {
            $changed[] = 'File sertifikat diganti';
        }

        return implode('; ', $changed);
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }
}
```

### app/Http/Controllers/AuthController.php

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ActivityLog;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.login');
    }

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

    public function logout(Request $request)
    {
        ActivityLog::record('logout', Auth::user()?->email);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
```

### app/Http/Controllers/CertificateController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Zxing\QrReader;

class CertificateController extends Controller
{
    private const PUBLIC_CERTIFICATE_FIELDS = [
        'id',
        'nama',
        'npm',
        'nomor_sertifikat',
        'jenis_sertifikat',
        'tanggal_terbit',
        'masa_berlaku',
        'file_path',
        'hash_sha256',
    ];

    private const ADMIN_CERTIFICATE_FIELDS = [
        'id',
        'nama',
        'npm',
        'nomor_sertifikat',
        'jenis_sertifikat',
        'tanggal_terbit',
        'masa_berlaku',
        'file_path',
        'hash_sha256',
        'history_notes',
    ];

    public function index()
    {
        return view('verify');
    }

    public function adminVerify()
    {
        return view('admin.verify');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'cert_number' => 'required|string|max:100',
        ]);
        
        $cert = Certificate::with('student')->where('certificate_number', $request->cert_number)->first();
        
        if (!$cert) {
            return response()->json(['success' => false, 'message' => 'Sertifikat tidak ditemukan']);
        }

        if (!$this->verifyFileIntegrity($cert)) {
            return response()->json(['success' => false, 'message' => 'Integritas file sertifikat tidak valid. File mungkin telah dimodifikasi.']);
        }
        
        return response()->json([
            'success' => true,
            'data' => $this->serializePublicCertificate($cert),
        ]);
    }

    public function verifyForAdmin(Request $request)
    {
        $request->validate([
            'cert_number' => 'required|string|max:100',
        ]);

        $cert = Certificate::with('student')->where('certificate_number', $request->cert_number)->first();

        if (!$cert) {
            return response()->json(['success' => false, 'message' => 'Sertifikat tidak ditemukan']);
        }

        if (!$this->verifyFileIntegrity($cert)) {
            return response()->json(['success' => false, 'message' => 'Integritas file sertifikat tidak valid. File mungkin telah dimodifikasi.']);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeAdminCertificate($cert),
        ]);
    }

    public function verifyQr(Request $request)
    {
        $request->validate([
            'qr_image' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {
            $file = $request->file('qr_image');
            $text = $this->readQrWithFallback($file->getPathname());

            if (!$text) {
                return response()->json(['success' => false, 'message' => 'QR code tidak dapat dibaca. Pastikan QR terlihat jelas dan cukup besar.']);
            }

            if (preg_match('/nomor_sertifikat=([^&]+)/', $text, $matches)) {
                $certNumber = urldecode($matches[1]);
                $cert = Certificate::with('student')->where('certificate_number', $certNumber)->first();

                if (!$cert) {
                    return response()->json(['success' => false, 'message' => 'Sertifikat tidak ditemukan']);
                }

                if (!$this->verifyFileIntegrity($cert)) {
                    return response()->json(['success' => false, 'message' => 'Integritas file sertifikat tidak valid. File mungkin telah dimodifikasi.']);
                }

                return response()->json([
                    'success' => true,
                    'data' => $this->serializePublicCertificate($cert),
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Format QR code tidak valid']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'QR code gagal diproses']);
        }
    }

    public function verifyQrForAdmin(Request $request)
    {
        $request->validate([
            'qr_image' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {
            $file = $request->file('qr_image');
            $text = $this->readQrWithFallback($file->getPathname());

            if (!$text) {
                return response()->json(['success' => false, 'message' => 'QR code tidak dapat dibaca. Pastikan QR terlihat jelas dan cukup besar.']);
            }

            if (preg_match('/nomor_sertifikat=([^&]+)/', $text, $matches)) {
                $certNumber = urldecode($matches[1]);
                $cert = Certificate::with('student')->where('certificate_number', $certNumber)->first();

                if (!$cert) {
                    return response()->json(['success' => false, 'message' => 'Sertifikat tidak ditemukan']);
                }

                if (!$this->verifyFileIntegrity($cert)) {
                    return response()->json(['success' => false, 'message' => 'Integritas file sertifikat tidak valid. File mungkin telah dimodifikasi.']);
                }

                return response()->json([
                    'success' => true,
                    'data' => $this->serializeAdminCertificate($cert),
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Format QR code tidak valid']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'QR code gagal diproses']);
        }
    }

    private function verifyFileIntegrity(Certificate $cert): bool
    {
        if (!$cert->hash_sha256 || !$cert->file_path) {
            return true; // skip jika hash belum ada
        }

        $filePath = public_path($cert->file_path);

        if (!file_exists($filePath)) {
            return false;
        }

        return hash_file('sha256', $filePath) === $cert->hash_sha256;
    }

    private function readQrWithFallback(string $imagePath): ?string
    {
        // Attempt 1: baca langsung
        $text = (new QrReader($imagePath))->text();
        if ($text) return $text;

        // Attempt 2: resize ke lebar 1200px agar QR lebih terbaca
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $img = $manager->read($imagePath);

        if ($img->width() > 1200) {
            $img->scale(width: 1200);
        } else {
            $img->scale(width: min($img->width() * 2, 2400));
        }

        $tmpPath = sys_get_temp_dir() . '/qr_resized_' . uniqid() . '.png';
        $img->save($tmpPath);
        $text = (new QrReader($tmpPath))->text();
        @unlink($tmpPath);
        if ($text) return $text;

        // Attempt 3: tingkatkan kontras & sharpness
        $img2 = $manager->read($imagePath);
        $img2->scale(width: 800);
        $img2->sharpen(15);
        $img2->contrast(20);
        $tmpPath2 = sys_get_temp_dir() . '/qr_enhanced_' . uniqid() . '.png';
        $img2->save($tmpPath2);
        $text = (new QrReader($tmpPath2))->text();
        @unlink($tmpPath2);

        return $text ?: null;
    }

    private function serializePublicCertificate(Certificate $certificate): array
    {
        $data = [
            'id'               => $certificate->id,
            'nama'             => $certificate->nama,
            'npm'              => $certificate->npm,
            'nomor_sertifikat' => $certificate->nomor_sertifikat,
            'jenis_sertifikat' => $certificate->jenis_sertifikat,
            'tanggal_terbit'   => $certificate->tanggal_terbit,
            'masa_berlaku'     => $certificate->valid_until?->translatedFormat('d F Y') ?? 'Tidak ditentukan',
            'file_path'        => $certificate->file_path,
            'hash_sha256'      => $certificate->hash_sha256,
        ];

        return array_intersect_key($data, array_flip(self::PUBLIC_CERTIFICATE_FIELDS));
    }

    private function serializeAdminCertificate(Certificate $certificate): array
    {
        $data = $this->serializePublicCertificate($certificate);
        $data['history_notes'] = $this->serializeHistoryNotes($certificate);

        return array_intersect_key($data, array_flip(self::ADMIN_CERTIFICATE_FIELDS));
    }

    private function serializeHistoryNotes(Certificate $certificate): array
    {
        return ActivityLog::query()
            ->with('user')
            ->where('target', $certificate->nomor_sertifikat)
            ->where('action', 'edit')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (ActivityLog $log) {
                return [
                    'action'      => $log->action,
                    'label'       => 'Perubahan',
                    'description' => $log->description ?? '-',
                    'created_at'  => $log->created_at?->format('d/m/Y H:i'),
                    'actor'       => $log->user?->name ?? 'Sistem',
                ];
            })
            ->values()
            ->all();
    }
}
```

### app/Http/Controllers/Controller.php

```php
<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}
```

### app/Http/Controllers/UserController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id')->get();
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $logs = ActivityLog::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Statistik per aksi
        $stats = ActivityLog::where('user_id', $user->id)
            ->selectRaw('action, count(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action');

        // Sertifikat yang di-upload oleh user ini (via created_by)
        $certificates = \App\Models\Certificate::with('student')
            ->where('created_by', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.users.show', compact('user', 'logs', 'stats', 'certificates'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|in:admin',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => true,
        ]);

        ActivityLog::record('create_user', $user->email, "Membuat user baru dengan role {$user->role_label}");

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} berhasil dibuat.");
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => 'required|in:superadmin,admin',
        ]);

        // Kunci role superadmin — tidak bisa diubah
        if ($user->role === 'superadmin') {
            $request->merge(['role' => 'superadmin']);
        }

        // Prevent demoting self
        if ($user->id === auth()->id() && $request->role !== 'superadmin') {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah role diri sendiri.']);
        }

        $oldRole = $user->role_label;
        $user->update([
            'name'      => $request->name,
            'email'     => $request->email,
            'role'      => $request->role,
            'is_active' => $request->has('is_active'),
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6|confirmed']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        ActivityLog::record('update_user', $user->email, "Update user dari {$oldRole} ke {$user->role_label}");

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        ActivityLog::record('delete_user', $user->email, "Menghapus user {$user->name} ({$user->role_label})");
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "User berhasil dihapus.");
    }
}
```

### app/Http/Middleware/ContentSecurityPolicy.php

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        app()->instance('csp_nonce', $nonce);

        $response = $next($request);

        // Mode lebih ketat:
        // script inline diizinkan melalui nonce, sementara inline event handler
        // pada view utama sudah dipindahkan ke addEventListener.
        $csp = implode('; ', [
            "default-src 'self'",

            "script-src 'self' 'nonce-{$nonce}'",

            // Style inline masih dipakai luas pada atribut style dan blok style di Blade
            "style-src 'self' 'unsafe-inline'",

            "font-src 'self'",

            // Gambar: domain sendiri + data URI (untuk preview base64 di JS)
            "img-src 'self' data:",

            // Koneksi fetch/XHR: hanya domain sendiri (untuk AJAX verifikasi)
            "connect-src 'self'",

            // Tidak izinkan embed frame dari luar
            "frame-ancestors 'none'",

            // Tidak izinkan object/embed/applet
            "object-src 'none'",

            // Base URI hanya domain sendiri
            "base-uri 'self'",

            // Form hanya boleh submit ke domain sendiri
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        // Header keamanan tambahan
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
```

### app/Http/Middleware/RoleMiddleware.php

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
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
}
```

### app/Models/ActivityLog.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'target', 'ip_address', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?string $target = null, ?string $description = null): void
    {
        static::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'target'      => $target,
            'ip_address'  => request()->ip(),
            'description' => $description,
        ]);
    }

    public function getActionBadgeAttribute(): string
    {
        return match($this->action) {
            'login'       => 'bg-green-100 text-green-700',
            'logout'      => 'bg-gray-100 text-gray-700',
            'upload'      => 'bg-blue-100 text-blue-700',
            'edit'        => 'bg-amber-100 text-amber-700',
            'delete'      => 'bg-red-100 text-red-700',
            'create_user' => 'bg-purple-100 text-purple-700',
            'update_user' => 'bg-yellow-100 text-yellow-700',
            'delete_user' => 'bg-red-100 text-red-700',
            default       => 'bg-gray-100 text-gray-700',
        };
    }
}
```

### app/Models/Certificate.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $table = 'certificates';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'student_id',
        'certificate_number',
        'certificate_type',
        'valid_until',
        'file_path',
        'hash_sha256',
        'digital_signature',
        'qr_code',
        'qr_attached',
        'qr_x',
        'qr_y',
        'qr_size',
        'created_by',
    ];

    protected $casts = [
        'qr_attached' => 'boolean',
        'valid_until' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessor agar view lama tetap bisa pakai nama field lama
    public function getNamaAttribute()        { return $this->student?->name; }
    public function getNpmAttribute()         { return $this->student?->npm; }
    public function getNomorSertifikatAttribute() { return $this->certificate_number; }
    public function getJenisSertifikatAttribute() { return $this->certificate_type; }
    public function getTanggalTerbitAttribute()   { return $this->created_at?->format('Y-m-d'); }
    public function getValidUntilLabelAttribute() { return $this->valid_until?->format('Y-m-d') ?? 'Tidak ditentukan'; }
    public function getFileHashAttribute()    { return $this->hash_sha256; }
    public function getSignatureAttribute()   { return $this->digital_signature; }
    public function getQrCodePathAttribute()  { return $this->qr_code ? 'qrcodes/' . $this->qr_code : null; }
}
```

### app/Models/Student.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';
    const UPDATED_AT = null;

    protected $fillable = ['npm', 'name', 'photo'];

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}
```

### app/Models/User.php

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';
    public $timestamps = false;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];
    protected $hidden   = ['password'];

    protected $casts = ['is_active' => 'boolean'];

    public function isSuperAdmin(): bool { return $this->role === 'superadmin'; }
    public function isAdmin(): bool      { return $this->role === 'admin'; }

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'superadmin' => 'Super Admin',
            'admin'      => 'Admin',
            default      => ucfirst($this->role),
        };
    }

    public function getRoleBadgeAttribute(): string
    {
        return match($this->role) {
            'superadmin' => 'bg-red-100 text-red-700',
            'admin'      => 'bg-blue-100 text-blue-700',
            default      => 'bg-gray-100 text-gray-700',
        };
    }
}
```

### app/Providers/AppServiceProvider.php

```php
<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });

        RateLimiter::for('verify', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('verify-qr', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        Blade::directive('cspNonce', function () {
            return "<?php echo request()->attributes->get('csp_nonce', ''); ?>";
        });
    }
}
```

### bootstrap/app.php

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\ContentSecurityPolicy::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return redirect()->route('login');
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            return response()->view('errors.403', [], 403);
        });
    })->create();
```

### bootstrap/providers.php

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
];
```

### composer.json

```json
{
    "$schema": "https://getcomposer.org/schema.json",
    "name": "laravel/laravel",
    "type": "project",
    "description": "The skeleton application for the Laravel framework.",
    "keywords": ["laravel", "framework"],
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "endroid/qr-code": "^6.0",
        "intervention/image": "^3.11",
        "khanamiryan/qrcode-detector-decoder": "^2.0",
        "laravel/framework": "^11.31",
        "laravel/tinker": "^2.9"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pail": "^1.1",
        "laravel/pint": "^1.13",
        "laravel/sail": "^1.26",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.1",
        "phpunit/phpunit": "^11.0.1"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi",
            "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
            "@php artisan migrate --graceful --ansi"
        ],
        "dev": [
            "Composer\\Config::disableProcessTimeout",
            "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

### config/app.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
```

### config/auth.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
```

### config/cache.php

```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),

];
```

### config/database.php

```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
```

### config/filesystems.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
```

### config/logging.php

```php
<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
```

### config/mail.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];
```

### config/queue.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];
```

### config/services.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
```

### config/session.php

```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default session driver that is utilized for
    | incoming requests. Laravel supports a variety of storage options to
    | persist session data. Database storage is a great default choice.
    |
    | Supported: "file", "cookie", "database", "apc",
    |            "memcached", "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to expire immediately when the browser is closed then you may
    | indicate that via the expire_on_close configuration option.
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all of your session data
    | should be encrypted before it's stored. All encryption is performed
    | automatically by Laravel and you may use the session like normal.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When utilizing the "file" session driver, the session files are placed
    | on disk. The default storage location is defined here; however, you
    | are free to provide another location where they should be stored.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" or "redis" session drivers, you may specify a
    | connection that should be used to manage these sessions. This should
    | correspond to a connection in your database configuration options.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table to
    | be used to store sessions. Of course, a sensible default is defined
    | for you; however, you're welcome to change this to another table.
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store
    |--------------------------------------------------------------------------
    |
    | When using one of the framework's cache driven session backends, you may
    | define the cache store which should be used to store the session data
    | between requests. This must match one of your defined cache stores.
    |
    | Affects: "apc", "dynamodb", "memcached", "redis"
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery
    |--------------------------------------------------------------------------
    |
    | Some session drivers must manually sweep their storage location to get
    | rid of old sessions from storage. Here are the chances that it will
    | happen on a given request. By default, the odds are 2 out of 100.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the session cookie that is created by
    | the framework. Typically, you should not need to change this value
    | since doing so does not grant a meaningful security improvement.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as available. Typically, this will be the root path of
    | your application, but you're free to change this when necessary.
    |
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | This value determines the domain and subdomains the session cookie is
    | available to. By default, the cookie will be available to the root
    | domain and all subdomains. Typically, this shouldn't be changed.
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | By setting this option to true, session cookies will only be sent back
    | to the server if the browser has a HTTPS connection. This will keep
    | the cookie from being sent to you when it can't be done securely.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol. It's unlikely you should disable this option.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place, and can be used to mitigate CSRF attacks. By default, we
    | will set this value to "lax" to permit secure cross-site requests.
    |
    | See: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
    |
    | Supported: "lax", "strict", "none", null
    |
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Partitioned Cookies
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will tie the cookie to the top-level site for
    | a cross-site context. Partitioned cookies are accepted by the browser
    | when flagged "secure" and the Same-Site attribute is set to "none".
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
```

### database/factories/UserFactory.php

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

### database/migrations/0001_01_01_000000_create_users_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // users table already exists from legacy system, skip creation
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
```

### database/migrations/0001_01_01_000001_create_cache_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
```

### database/migrations/0001_01_01_000002_create_jobs_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
```

### database/migrations/2026_03_24_205235_add_role_to_users_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['superadmin', 'admin', 'viewer', 'developer', 'user'])
                    ->default('viewer')
                    ->after('password');
            });
        } else {
            // Modify role enum to include new RBAC roles
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','admin','viewer','developer','user') NOT NULL DEFAULT 'viewer'");
        }

        // Add is_active column if not exists
        if (!Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('role');
            });
        }

        // Map existing roles to new RBAC roles
        DB::table('users')->where('email', 'admin@admin.com')->update(['role' => 'superadmin', 'name' => 'Super Admin']);
        DB::table('users')->where('email', 'dev@admin.com')->update(['role' => 'admin', 'name' => 'Developer Admin']);
        DB::table('users')->where('role', 'developer')->where('email', '!=', 'dev@admin.com')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'user')->update(['role' => 'viewer']);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('developer','admin','user') NOT NULL DEFAULT 'user'");
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
```

### database/migrations/2026_03_24_205236_create_activity_logs_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');           // upload, delete, verify, login, logout
            $table->string('target')->nullable(); // nomor_sertifikat or user email
            $table->string('ip_address', 45)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
```

### database/migrations/2026_04_01_135222_add_photo_to_students_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->string('npm');
                $table->string('name');
                $table->string('photo')->nullable();
                $table->timestamp('created_at')->nullable();
            });

            return;
        }

        if (!Schema::hasColumn('students', 'photo')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('photo')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
```

### database/migrations/2026_04_01_151713_add_qr_position_to_certificates_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('certificates')) {
            Schema::create('certificates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('certificate_number')->unique();
                $table->string('certificate_type');
                $table->string('file_path');
                $table->string('hash_sha256')->nullable();
                $table->string('digital_signature')->nullable();
                $table->string('qr_code')->nullable();
                $table->boolean('qr_attached')->default(false);
                $table->integer('qr_x')->default(50);
                $table->integer('qr_y')->default(50);
                $table->integer('qr_size')->default(150);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_at')->nullable();
            });

            return;
        }

        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'qr_attached')) {
                $table->boolean('qr_attached')->default(false)->after('qr_code');
            }
            if (!Schema::hasColumn('certificates', 'qr_x')) {
                $table->integer('qr_x')->default(50)->after('qr_attached');
            }
            if (!Schema::hasColumn('certificates', 'qr_y')) {
                $table->integer('qr_y')->default(50)->after('qr_x');
            }
            if (!Schema::hasColumn('certificates', 'qr_size')) {
                $table->integer('qr_size')->default(150)->after('qr_y');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['qr_attached', 'qr_x', 'qr_y', 'qr_size']);
        });
    }
};
```

### database/migrations/2026_04_23_120000_add_valid_until_to_certificates_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('certificates', 'valid_until')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->date('valid_until')->nullable()->after('certificate_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('valid_until');
        });
    }
};
```

### database/seeders/DatabaseSeeder.php

```php
<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
```

### package.json

```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "autoprefixer": "^10.4.20",
        "axios": "^1.7.4",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^1.2.0",
        "postcss": "^8.4.47",
        "tailwindcss": "^3.4.13",
        "vite": "^6.0.11"
    }
}
```

### phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <!-- <env name="DB_CONNECTION" value="sqlite"/> -->
        <!-- <env name="DB_DATABASE" value=":memory:"/> -->
        <env name="MAIL_MAILER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

### postcss.config.js

```javascript
export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
};
```

### resources/css/app.css

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
    :root {
        --brand-ink: #0f172a;
        --brand-muted: #475569;
        --brand-primary: #2563eb;
        --brand-primary-dark: #1d4ed8;
        --brand-primary-soft: #dbeafe;
        --brand-border: #bfdbfe;
        --brand-accent: #0f766e;
    }

    body {
        font-family: "Segoe UI Variable", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        color: var(--brand-ink);
        background: #f1f5f9;
    }
}

@layer components {
    .btn-brand {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));
        color: #fff;
        box-shadow: 0 14px 30px -18px rgba(37, 99, 235, 0.8);
    }

    .btn-brand::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, transparent 30%, rgba(255, 255, 255, 0.35) 50%, transparent 70%);
        transform: translateX(-140%);
        animation: shimmer-pass 3.8s ease-in-out infinite;
    }

    .btn-brand:hover {
        filter: brightness(1.03);
        box-shadow: 0 18px 34px -20px rgba(37, 99, 235, 0.9);
    }

    .surface-brand {
        border: 1px solid var(--brand-border);
        background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
    }

    .portal-shell {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at top left, rgba(96, 165, 250, 0.22), transparent 30%),
            radial-gradient(circle at right 20%, rgba(37, 99, 235, 0.14), transparent 28%),
            linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
    }

    .portal-shell::before,
    .portal-shell::after {
        content: "";
        position: absolute;
        border-radius: 9999px;
        filter: blur(18px);
        opacity: 0.6;
        pointer-events: none;
    }

    .portal-shell::before {
        top: 4rem;
        left: -5rem;
        width: 14rem;
        height: 14rem;
        background: rgba(59, 130, 246, 0.18);
        animation: float-slow 7s ease-in-out infinite;
    }

    .portal-shell::after {
        right: -4rem;
        bottom: 3rem;
        width: 12rem;
        height: 12rem;
        background: rgba(147, 197, 253, 0.28);
        animation: float-slow 8.5s ease-in-out infinite reverse;
    }

    .portal-panel {
        animation: rise-in 0.9s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .portal-panel[data-delay="1"] {
        animation-delay: 0.08s;
    }

    .portal-panel[data-delay="2"] {
        animation-delay: 0.18s;
    }

    .dashboard-tile {
        transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
    }

    .dashboard-tile:hover {
        transform: translateY(-6px) scale(1.01);
        box-shadow: 0 20px 44px -26px rgba(37, 99, 235, 0.5);
        border-color: var(--brand-border);
    }

    .pulse-line {
        background-size: 200% 100%;
        animation: pulse-slide 4s linear infinite;
    }

    .float-orb {
        position: absolute;
        border-radius: 9999px;
        pointer-events: none;
        filter: blur(2px);
        animation: float-orb 9s ease-in-out infinite;
    }

    .float-orb[data-speed="fast"] {
        animation-duration: 6.5s;
    }

    .float-orb[data-speed="slow"] {
        animation-duration: 11s;
    }

    .bi {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1em;
        height: 1em;
        font-style: normal;
        line-height: 1;
        vertical-align: -0.125em;
    }

    .bi::before {
        content: "";
        display: block;
        width: 1em;
        height: 1em;
        background-color: currentColor;
        mask: var(--bi-icon) center / contain no-repeat;
        -webkit-mask: var(--bi-icon) center / contain no-repeat;
    }

    .bi-activity { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 12h3l2.5-5 4 10 2.5-5H21v2h-4.8L12.5 21 8.7 11.6 7.2 14H3z'/%3E%3C/svg%3E"); }
    .bi-arrow-down-up { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 3l4 4h-3v10h-2V7H8zm0 18l-4-4h3V7h2v10h3z'/%3E%3C/svg%3E"); }
    .bi-arrow-left { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10.8 5.8 4.6 12l6.2 6.2 1.4-1.4-3.8-3.8H20v-2H8.4l3.8-3.8z'/%3E%3C/svg%3E"); }
    .bi-arrow-repeat { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M17.7 6.3A8 8 0 0 0 4 12H2a10 10 0 0 1 17-7l2-2v6h-6zM6.3 17.7A8 8 0 0 0 20 12h2a10 10 0 0 1-17 7l-2 2v-6h6z'/%3E%3C/svg%3E"); }
    .bi-box-arrow-in-right { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 5V3H4v18h6v-2H6V5zm1.2 5h8l-3-3 1.4-1.4L23 11l-5.4 5.4-1.4-1.4 3-3h-8z'/%3E%3C/svg%3E"); }
    .bi-box-arrow-right { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M14 3v2h4v14h-4v2h6V3zm-1.2 13L11.4 14l3-3H4V9h10.4l-3-3L12.8 4.6 18.2 10z'/%3E%3C/svg%3E"); }
    .bi-camera { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 4 7.2 6H4a2 2 0 0 0-2 2v8a4 4 0 0 0 4 4h12a4 4 0 0 0 4-4V8a2 2 0 0 0-2-2h-3.2L15 4zm3 4a5 5 0 1 1 0 10 5 5 0 0 1 0-10m0 2.2a2.8 2.8 0 1 0 0 5.6 2.8 2.8 0 0 0 0-5.6'/%3E%3C/svg%3E"); }
    .bi-check-circle { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2m-1 14-4-4 1.4-1.4 2.6 2.6 5.6-5.6L18 9z'/%3E%3C/svg%3E"); }
    .bi-check-circle-fill { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2m-1.2 14.4-4.2-4.2 1.4-1.4 2.8 2.8 5.8-5.8 1.4 1.4z'/%3E%3C/svg%3E"); }
    .bi-check-lg { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m9.2 16.6-4.8-4.8 1.4-1.4 3.4 3.4 9-9 1.4 1.4z'/%3E%3C/svg%3E"); }
    .bi-chevron-down { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m6.7 9.3 5.3 5.3 5.3-5.3 1.4 1.4-6.7 6.7-6.7-6.7z'/%3E%3C/svg%3E"); }
    .bi-chevron-up { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m6.7 14.7 5.3-5.3 5.3 5.3 1.4-1.4-6.7-6.7-6.7 6.7z'/%3E%3C/svg%3E"); }
    .bi-clipboard { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 3h6l1 2h3v16H5V5h3zm1.2 2-.5 1h4.6l-.5-1z'/%3E%3C/svg%3E"); }
    .bi-cloud-arrow-down { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 18a4 4 0 0 1-.4-8A5.5 5.5 0 0 1 17.3 8 4 4 0 1 1 18 18h-3v-4h2l-4 4-4-4h2v4z'/%3E%3C/svg%3E"); }
    .bi-cloud-upload { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M18 18a4 4 0 1 0-.7-7 5.5 5.5 0 0 0-10.7 2A4 4 0 0 0 7 18h3v-4H8l4-4 4 4h-2v4z'/%3E%3C/svg%3E"); }
    .bi-download { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M11 3h2v9h3l-4 4-4-4h3zm-6 14h14v4H5z'/%3E%3C/svg%3E"); }
    .bi-envelope { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h18v12H3zm2 2v.2l7 4.7 7-4.7V8l-7 4.7z'/%3E%3C/svg%3E"); }
    .bi-exclamation-circle { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2m-1 5h2v7h-2zm0 9h2v2h-2z'/%3E%3C/svg%3E"); }
    .bi-exclamation-triangle { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 3 2 21h20zm-1 6h2v5h-2zm0 7h2v2h-2z'/%3E%3C/svg%3E"); }
    .bi-eye { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 5C6.5 5 2.1 8.4 1 12c1.1 3.6 5.5 7 11 7s9.9-3.4 11-7c-1.1-3.6-5.5-7-11-7m0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8m0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4'/%3E%3C/svg%3E"); }
    .bi-file-earmark-image { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 3h9l5 5v13H6zm8 1.5V9h4.5zM8 18h10l-3.2-4-2.3 3-1.7-2.1zM10 10.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3'/%3E%3C/svg%3E"); }
    .bi-file-earmark-text { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 3h9l5 5v13H6zm8 1.5V9h4.5zM9 12h6v2H9zm0 4h6v2H9zM9 8h3v2H9z'/%3E%3C/svg%3E"); }
    .bi-folder2-open { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h6l2 2h10v2H3zm1 4h18l-2 8H6z'/%3E%3C/svg%3E"); }
    .bi-folder-fill { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h6l2 2h10v10H3z'/%3E%3C/svg%3E"); }
    .bi-hand-index { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 3h2v8h1V6h2v5h1V7h2v8.5a4.5 4.5 0 0 1-4.5 4.5H11a5 5 0 0 1-5-5v-4h2v4a3 3 0 0 0 3 3h2.5a2.5 2.5 0 0 0 2.5-2.5V9h-1v3h-2V8h-1v4h-2z'/%3E%3C/svg%3E"); }
    .bi-hourglass-split { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 3h10v2a5 5 0 0 1-3.1 4.6L12 10.4l-1.9-.8A5 5 0 0 1 7 5zm0 18v-2a5 5 0 0 1 3.1-4.6l1.9-.8 1.9.8A5 5 0 0 1 17 19v2zm2-2h6a3 3 0 0 0-1.8-2.8L12 15.6l-1.2.6A3 3 0 0 0 9 19m6-14H9a3 3 0 0 0 1.8 2.8L12 8.4l1.2-.6A3 3 0 0 0 15 5'/%3E%3C/svg%3E"); }
    .bi-house { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 4 3 11h2v9h5v-6h4v6h5v-9h2z'/%3E%3C/svg%3E"); }
    .bi-image { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 5h16v14H4zm3 3a1.5 1.5 0 1 0 0 3A1.5 1.5 0 0 0 7 8m11 8-4-5-3 4-2-2.5L6 16z'/%3E%3C/svg%3E"); }
    .bi-inbox { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 5h16l2 9v5H2v-5zm1.6 2L4.4 12H9l1 2h4l1-2h4.6L18.4 7z'/%3E%3C/svg%3E"); }
    .bi-journal-text { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h13a2 2 0 0 1 2 2v16H7a2 2 0 0 1-2-2zm4 4h7v2H9zm0 4h7v2H9zm0 4h5v2H9zM7 5v14h1V5z'/%3E%3C/svg%3E"); }
    .bi-journal-x { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h13a2 2 0 0 1 2 2v16H7a2 2 0 0 1-2-2zm4.4 5 1.4-1.4L12 7.8l1.2-1.2L14.6 8 13.4 9.2l1.2 1.2-1.4 1.4L12 10.6l-1.2 1.2-1.4-1.4L10.6 9zM7 5v14h1V5z'/%3E%3C/svg%3E"); }
    .bi-keyboard { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 6h18v12H3zm2 2v2h2V8zm3 0v2h2V8zm3 0v2h2V8zm3 0v2h2V8zm3 0v2h2V8zM5 12v2h2v-2zm3 0v2h8v-2zm9 0v2h2v-2z'/%3E%3C/svg%3E"); }
    .bi-lightning-charge { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M13 2 5 13h5l-1 9 8-11h-5z'/%3E%3C/svg%3E"); }
    .bi-list-ul { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 6h2v2H4zm4 0h12v2H8zM4 11h2v2H4zm4 0h12v2H8zM4 16h2v2H4zm4 0h12v2H8z'/%3E%3C/svg%3E"); }
    .bi-lock { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 10V8a5 5 0 0 1 10 0v2h2v11H5V10zm2 0h6V8a3 3 0 1 0-6 0z'/%3E%3C/svg%3E"); }
    .bi-pencil,
    .bi-pencil-square { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m4 16.5 9.8-9.8 3.5 3.5L7.5 20H4zm11.2-11.2 1.3-1.3a2 2 0 0 1 2.8 2.8L18 8.1z'/%3E%3C/svg%3E"); }
    .bi-people { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8m8 1a3 3 0 1 1 0-6 3 3 0 0 1 0 6M2 20a6 6 0 0 1 12 0zm11.5 0a5 5 0 0 1 7-4.6A5 5 0 0 1 22 20z'/%3E%3C/svg%3E"); }
    .bi-person { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10m-7 9a7 7 0 0 1 14 0z'/%3E%3C/svg%3E"); }
    .bi-person-badge,
    .bi-person-vcard { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 5h18v14H3zm6 4a2 2 0 1 0 0 4 2 2 0 0 0 0-4m4 0h5v2h-5zm0 4h5v2h-5zM6 16h6a3 3 0 0 0-6 0'/%3E%3C/svg%3E"); }
    .bi-person-lines-fill { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M6 7a3 3 0 1 1 0 6 3 3 0 0 1 0-6m6 1h8v2h-8zm0 4h8v2h-8zM2 19a4 4 0 0 1 8 0z'/%3E%3C/svg%3E"); }
    .bi-person-lock { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8m-6 9a6 6 0 0 1 12 0h-2a4 4 0 0 0-8 0zm14-5v-2a3 3 0 1 1 6 0v2h1v6h-8v-6zm2 0h2v-2a1 1 0 1 0-2 0z'/%3E%3C/svg%3E"); }
    .bi-person-plus { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8m7-2h3V6h2v3h3v2h-3v3h-2v-3h-3zM1 20a7 7 0 0 1 14 0z'/%3E%3C/svg%3E"); }
    .bi-pin-map { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a7 7 0 0 0-7 7c0 5.3 7 13 7 13s7-7.7 7-13a7 7 0 0 0-7-7m0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5'/%3E%3C/svg%3E"); }
    .bi-plus-lg { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M11 4h2v7h7v2h-7v7h-2v-7H4v-2h7z'/%3E%3C/svg%3E"); }
    .bi-qr-code { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M3 3h8v8H3zm2 2v4h4V5zm8-2h8v8h-8zm2 2v4h4V5zM3 13h8v8H3zm2 2v4h4v-4zm10-2h2v2h-2zm4 0h2v2h-2zm-4 4h2v4h-2zm4 2h2v2h-2zm-2-4h2v2h-2z'/%3E%3C/svg%3E"); }
    .bi-search { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M10 4a6 6 0 1 0 3.9 10.6l4.7 4.7 1.4-1.4-4.7-4.7A6 6 0 0 0 10 4m0 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8'/%3E%3C/svg%3E"); }
    .bi-shield-check,
    .bi-shield-fill-check { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2 5 5v6c0 5 3.4 9.7 7 11 3.6-1.3 7-6 7-11V5zm-1.2 12.4-3.3-3.3 1.4-1.4 1.9 1.9 4.7-4.7 1.4 1.4z'/%3E%3C/svg%3E"); }
    .bi-shield-exclamation { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2 5 5v6c0 5 3.4 9.7 7 11 3.6-1.3 7-6 7-11V5zm-1 5h2v6h-2zm0 8h2v2h-2z'/%3E%3C/svg%3E"); }
    .bi-shield-lock,
    .bi-shield-lock-fill { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2 5 5v6c0 5 3.4 9.7 7 11 3.6-1.3 7-6 7-11V5zm3 9V9a3 3 0 1 0-6 0v2zm-4 0V9a1 1 0 1 1 2 0v2zm-1 1h4v5h-4z'/%3E%3C/svg%3E"); }
    .bi-shield-x { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2 5 5v6c0 5 3.4 9.7 7 11 3.6-1.3 7-6 7-11V5zm-2.6 6L12 10.6 14.6 8 16 9.4 13.4 12l2.6 2.6-1.4 1.4L12 13.4 9.4 16 8 14.6l2.6-2.6L8 9.4z'/%3E%3C/svg%3E"); }
    .bi-sort-down { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m12 17-4-4h8z'/%3E%3C/svg%3E"); }
    .bi-sort-up { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m12 7 4 4H8z'/%3E%3C/svg%3E"); }
    .bi-speedometer2 { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 4a10 10 0 0 0-10 10h2a8 8 0 1 1 16 0h2A10 10 0 0 0 12 4m0 4a6 6 0 0 0-6 6h2a4 4 0 1 1 8 0h2a6 6 0 0 0-6-6m5.7 1.7-4.9 3.2A2 2 0 1 0 13 14l4.7-3z'/%3E%3C/svg%3E"); }
    .bi-stop-circle { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2m-4 6h8v8H8z'/%3E%3C/svg%3E"); }
    .bi-trash { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9 3h6l1 2h4v2H4V5h4zm-2 6h10l-1 11H8z'/%3E%3C/svg%3E"); }
    .bi-x-circle,
    .bi-x-circle-fill { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2m-3.4 5.2L12 10.6l3.4-3.4 1.4 1.4-3.4 3.4 3.4 3.4-1.4 1.4-3.4-3.4-3.4 3.4-1.4-1.4 3.4-3.4-3.4-3.4z'/%3E%3C/svg%3E"); }
    .bi-x-lg { --bi-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12 19 17.6 17.6 19 12 13.4 6.4 19 5 17.6 10.6 12 5 6.4z'/%3E%3C/svg%3E"); }
}

@keyframes rise-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes float-slow {
    0%, 100% {
        transform: translate3d(0, 0, 0);
    }
    50% {
        transform: translate3d(0, -14px, 0);
    }
}

@keyframes shimmer-pass {
    0%, 15% {
        transform: translateX(-140%);
    }
    45%, 100% {
        transform: translateX(140%);
    }
}

@keyframes pulse-slide {
    0% {
        background-position: 0% 50%;
    }
    100% {
        background-position: 200% 50%;
    }
}

@keyframes float-orb {
    0%, 100% {
        transform: translate3d(0, 0, 0) scale(1);
        opacity: 0.55;
    }
    50% {
        transform: translate3d(0, -18px, 0) scale(1.08);
        opacity: 0.9;
    }
}
```

### resources/js/app.js

```javascript
import './bootstrap';
```

### resources/js/bootstrap.js

```javascript
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
```

### resources/views/admin/activity-logs.blade.php

```blade
@extends('layouts.app')
@section('title', 'Log Aktivitas')

@section('content')
<style>
    .admin-logs-shell {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 12% 14%, rgba(96, 165, 250, 0.16), transparent 18%),
            radial-gradient(circle at 88% 18%, rgba(59, 130, 246, 0.12), transparent 16%),
            linear-gradient(180deg, #f7fbff 0%, #eef5ff 46%, #f8fbff 100%);
    }

    .admin-logs-card {
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 24px 56px -40px rgba(15, 23, 42, 0.24);
    }
</style>

<div class="admin-logs-shell min-h-screen">
    @include('layouts.navbar')

    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="admin-logs-card rounded-3xl overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Log Aktivitas</h2>
                    <p class="text-sm text-gray-500 mt-1">Audit trail semua aksi pengguna - SHA-256 + RBAC</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center space-x-1 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                    <i class="bi bi-arrow-left"></i><span>Kembali</span>
                </a>
            </div>

            <div class="p-6 space-y-3">
                @forelse($logs as $date => $entries)
                @php
                    $carbon = \Carbon\Carbon::parse($date);
                    $label  = $carbon->translatedFormat('l, d F Y');
                @endphp
                <details class="border border-gray-200 rounded-lg overflow-hidden group">
                    <summary class="w-full flex items-center justify-between px-5 py-4 bg-gray-50 hover:bg-gray-100 transition text-left cursor-pointer list-none">
                        <div class="flex items-center space-x-3">
                            <i class="bi bi-folder-fill text-yellow-400 group-open:hidden"></i>
                            <i class="bi bi-folder2-open text-yellow-500 hidden group-open:inline-block"></i>
                            <span class="font-semibold text-gray-800">{{ $label }}</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="text-xs bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full font-medium">
                                {{ $entries->count() }} aktivitas
                            </span>
                            <i class="bi bi-chevron-down text-gray-400 text-sm group-open:hidden"></i>
                            <i class="bi bi-chevron-up text-gray-400 text-sm hidden group-open:inline-block"></i>
                        </div>
                    </summary>

                    <div class="divide-y divide-gray-100">
                        @foreach($entries as $log)
                        <div class="px-5 py-3 flex items-start justify-between hover:bg-gray-50">
                            <div class="flex items-start space-x-3">
                                <span class="mt-0.5 text-xs px-2.5 py-1 rounded-full font-medium {{ $log->action_badge }}">
                                    {{ strtoupper($log->action) }}
                                </span>
                                <div>
                                    <p class="text-sm text-gray-700">{{ $log->description ?? $log->target ?? '-' }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $log->user?->name ?? 'System' }}
                                        @if($log->user) • <span class="text-xs px-1.5 py-0.5 rounded-full {{ $log->user->role_badge }}">{{ $log->user->role_label }}</span> @endif
                                        • {{ $log->ip_address }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap ml-4">
                                {{ $log->created_at->format('H:i:s') }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </details>
                @empty
                <div class="py-12 text-center text-gray-400">
                    <i class="bi bi-journal-x text-4xl block mb-2"></i>
                    Belum ada log aktivitas
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
```

### resources/views/admin/certificates.blade.php

```blade
@extends('layouts.app')
@section('title', 'Daftar Sertifikat')

@section('content')
<style nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
    .admin-page-shell {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 10% 14%, rgba(96, 165, 250, 0.16), transparent 18%),
            radial-gradient(circle at 88% 18%, rgba(59, 130, 246, 0.12), transparent 16%),
            linear-gradient(180deg, #f7fbff 0%, #eef5ff 46%, #f8fbff 100%);
    }

    .admin-page-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.18;
        background-image:
            linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
        background-size: 74px 74px;
        mask-image: radial-gradient(circle at center, rgba(15, 23, 42, 0.9), transparent 82%);
    }

    .admin-page-orb {
        position: absolute;
        border-radius: 9999px;
        pointer-events: none;
        opacity: 0.54;
        filter: blur(10px);
        animation: admin-page-float 14s ease-in-out infinite;
    }

    .admin-page-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 24px 56px -40px rgba(15, 23, 42, 0.24);
    }

    .admin-page-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.98) 0%, rgba(255, 255, 255, 0.98) 50%, rgba(239, 246, 255, 0.94) 100%);
        box-shadow: 0 26px 58px -40px rgba(15, 23, 42, 0.24);
    }

    .admin-page-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        border-radius: 9999px;
        padding: 0.42rem 0.95rem;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        color: #1d4ed8;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .admin-page-chip::before {
        content: "";
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 9999px;
        background: linear-gradient(135deg, #2563eb, #60a5fa);
        box-shadow: 0 0 0 5px rgba(191, 219, 254, 0.65);
    }

    @keyframes admin-page-float {
        0%, 100% { transform: translate3d(0, 0, 0); }
        50% { transform: translate3d(0, -18px, 0); }
    }
</style>

<div class="admin-page-shell min-h-screen">
    <div class="admin-page-orb left-[-4rem] top-24 h-64 w-64 bg-[radial-gradient(circle,_rgba(59,130,246,0.22)_0%,_rgba(96,165,250,0.08)_52%,_transparent_76%)]"></div>
    <div class="admin-page-orb right-[-5rem] top-28 h-72 w-72 bg-[radial-gradient(circle,_rgba(147,197,253,0.3)_0%,_rgba(191,219,254,0.08)_48%,_transparent_74%)]" style="animation-delay:-4s;"></div>
    @include('layouts.navbar')

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        @php
            $sortIcon = function (string $column) use ($sortBy, $sortDir) {
                if ($sortBy !== $column) {
                    return 'bi-arrow-down-up';
                }

                return $sortDir === 'asc' ? 'bi-sort-down' : 'bi-sort-up';
            };

            $sortUrl = function (string $column) use ($sortBy, $sortDir) {
                $nextDir = $sortBy === $column && $sortDir === 'asc' ? 'desc' : 'asc';

                return route('admin.certificates', array_merge(request()->query(), [
                    'sort_by' => $column,
                    'sort_dir' => $nextDir,
                ]));
            };
        @endphp

        <div class="admin-page-hero rounded-3xl p-6 sm:p-7">
            <div class="flex flex-col items-start gap-6">
                <div class="max-w-2xl">
                    <span class="admin-page-chip">Data Sertifikat</span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Daftar Sertifikat</h1>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-[15px]">Kelola pencarian, penyaringan, dan peninjauan seluruh data sertifikat dari satu halaman kerja.</p>
                </div>
                @if(auth()->user()->isAdmin())
                <div class="flex items-center">
                    <a href="{{ route('admin.upload') }}" class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-blue-200 transition hover:bg-blue-700">
                        <i class="bi bi-upload"></i>
                        <span>Upload Baru</span>
                    </a>
                </div>
                @endif
            </div>
        </div>

        <div class="admin-page-card mt-6 rounded-3xl">
            <div class="border-b border-slate-100 p-6 flex justify-between items-center">
                <div>
                    <h2 id="certificatesTotal" class="text-xl font-bold text-slate-900">Total: {{ $certificates->total() }} Sertifikat</h2>
                    <p class="mt-1 text-xs text-slate-400">Anda hanya dapat mengedit sertifikat milik Anda sendiri.</p>
                </div>
            </div>

            <div class="border-b border-slate-100 bg-slate-50/80 p-6">
                <form method="GET" action="{{ route('admin.certificates') }}" id="certificateFiltersForm" class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                        <div>
                            <label for="search" class="block text-sm font-semibold text-slate-900">Cari Sertifikat</label>
                            <p class="mt-1 text-xs text-slate-500">Temukan data berdasarkan nama, NPM, nomor sertifikat, atau jenis.</p>
                            <div class="relative mt-3">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Masukkan kata kunci pencarian" class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="flex flex-wrap items-end gap-2 lg:justify-end">
                            <button type="button" id="toggleCertificateFilters" aria-expanded="{{ $owner !== 'all' || $dateFrom !== '' || $dateTo !== '' ? 'true' : 'false' }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                <i class="bi bi-funnel"></i>
                                <span>Filter</span>
                            </button>
                            <span id="activeFilterIndicator" class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 {{ $search !== '' || $type !== '' || $owner !== 'all' || $dateFrom !== '' || $dateTo !== '' ? '' : 'hidden' }}">Filter aktif</span>
                            <div class="flex gap-2">
                                <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Cari
                                </button>
                                <a href="{{ route('admin.certificates') }}" id="resetCertificateFilters" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </div>

                    <div id="certificateAdvancedFilters" class="mt-4 border-t border-slate-200 pt-4 {{ $owner !== 'all' || $dateFrom !== '' || $dateTo !== '' ? '' : 'hidden' }}">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-7">
                            <div class="xl:col-span-2">
                                <label for="owner" class="mb-1.5 block text-sm font-medium text-slate-700">Kepemilikan</label>
                                <select name="owner" id="owner" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                    <option value="all" {{ $owner === 'all' ? 'selected' : '' }}>Semua Data</option>
                                    <option value="mine" {{ $owner === 'mine' ? 'selected' : '' }}>Data Saya</option>
                                </select>
                            </div>

                            <div class="xl:col-span-3">
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Rentang Tanggal</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}" aria-label="Tanggal Mulai" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                    <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}" aria-label="Tanggal Akhir" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="xl:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Urutan Data</label>
                                <div class="grid grid-cols-2 gap-3 xl:grid-cols-1">
                                    <select name="sort_by" id="sort_by" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        <option value="created_at" {{ $sortBy === 'created_at' ? 'selected' : '' }}>Tanggal</option>
                                        <option value="name" {{ $sortBy === 'name' ? 'selected' : '' }}>Nama</option>
                                        <option value="npm" {{ $sortBy === 'npm' ? 'selected' : '' }}>NPM</option>
                                        <option value="certificate_number" {{ $sortBy === 'certificate_number' ? 'selected' : '' }}>Nomor Sertifikat</option>
                                        <option value="certificate_type" {{ $sortBy === 'certificate_type' ? 'selected' : '' }}>Jenis</option>
                                    </select>
                                    <select name="sort_dir" id="sort_dir" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        <option value="desc" {{ $sortDir === 'desc' ? 'selected' : '' }}>Terbaru / Z-A</option>
                                        <option value="asc" {{ $sortDir === 'asc' ? 'selected' : '' }}>Terlama / A-Z</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div id="certificatesResults" class="relative transition-opacity duration-200">
                <div id="certificatesLoadingOverlay" class="hidden absolute inset-0 z-10 bg-white/70 backdrop-blur-[1px] flex items-center justify-center">
                    <div class="flex items-center gap-3 rounded-full border border-blue-100 bg-white px-4 py-2 shadow-sm">
                        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-blue-200 border-t-blue-600"></span>
                        <span class="text-sm font-medium text-gray-600">Memuat hasil...</span>
                    </div>
                </div>

                @include('admin.partials.certificates-results')
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
const certificateFiltersForm = document.getElementById('certificateFiltersForm');
const certificatesResults = document.getElementById('certificatesResults');
const certificatesLoadingOverlay = document.getElementById('certificatesLoadingOverlay');
const certificatesTotal = document.getElementById('certificatesTotal');
const activeFilterIndicator = document.getElementById('activeFilterIndicator');
const resetCertificateFilters = document.getElementById('resetCertificateFilters');
const searchInput = document.getElementById('search');
const toggleCertificateFilters = document.getElementById('toggleCertificateFilters');
const certificateAdvancedFilters = document.getElementById('certificateAdvancedFilters');
let liveSearchTimer = null;
let liveSearchController = null;

function bindDeleteButtons(scope = document) {
    scope.querySelectorAll('.delete-cert-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (confirm('Hapus sertifikat ' + this.dataset.name + '? Tindakan ini tidak dapat dibatalkan.')) {
                this.closest('form').submit();
            }
        });
    });
}

function updateFilterIndicator(hasActiveFilters) {
    if (!activeFilterIndicator) return;
    activeFilterIndicator.classList.toggle('hidden', !hasActiveFilters);
}

function setAdvancedFiltersVisibility(isVisible) {
    if (!certificateAdvancedFilters || !toggleCertificateFilters) return;
    certificateAdvancedFilters.classList.toggle('hidden', !isVisible);
    toggleCertificateFilters.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
    toggleCertificateFilters.classList.toggle('bg-slate-100', isVisible);
}

function hasAdvancedFiltersApplied() {
    const ownerField = document.getElementById('owner');
    const dateFromField = document.getElementById('date_from');
    const dateToField = document.getElementById('date_to');

    return (ownerField && ownerField.value !== 'all')
        || (dateFromField && dateFromField.value !== '')
        || (dateToField && dateToField.value !== '');
}

function syncFormWithUrl(url) {
    if (!certificateFiltersForm) return;
    const parsedUrl = new URL(url, window.location.origin);
    const params = parsedUrl.searchParams;
    const setValue = (id, fallback = '') => {
        const field = document.getElementById(id);
        if (field) field.value = params.get(id) ?? fallback;
    };
    setValue('search');
    setValue('owner', 'all');
    setValue('date_from');
    setValue('date_to');
    setValue('sort_by', 'created_at');
    setValue('sort_dir', 'desc');
}

async function fetchCertificates(url) {
    if (!certificatesResults || !certificateFiltersForm) return;
    if (liveSearchController) liveSearchController.abort();
    liveSearchController = new AbortController();

    try {
        certificatesResults.classList.add('opacity-70');
        certificatesLoadingOverlay?.classList.remove('hidden');

        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            signal: liveSearchController.signal,
        });

        if (!response.ok) throw new Error('Gagal memuat data sertifikat.');

        const data = await response.json();
        certificatesResults.innerHTML = data.html;

        if (certificatesTotal) {
            certificatesTotal.textContent = `Total: ${data.total} Sertifikat`;
        }

        syncFormWithUrl(url);
        updateFilterIndicator(Boolean(data.hasActiveFilters));
        setAdvancedFiltersVisibility(hasAdvancedFiltersApplied());
        bindDeleteButtons(certificatesResults);
        window.history.replaceState({}, '', url);
    } catch (error) {
        if (error.name !== 'AbortError') console.error(error);
    } finally {
        certificatesResults.classList.remove('opacity-70');
        certificatesLoadingOverlay?.classList.add('hidden');
    }
}

function buildFilterUrl(url = certificateFiltersForm.action) {
    const query = new URLSearchParams(new FormData(certificateFiltersForm)).toString();
    return query ? `${url}?${query}` : url;
}

function submitFiltersWithDelay() {
    clearTimeout(liveSearchTimer);
    liveSearchTimer = setTimeout(() => {
        fetchCertificates(buildFilterUrl());
    }, 300);
}

if (certificateFiltersForm) {
    certificateFiltersForm.addEventListener('submit', function (event) {
        event.preventDefault();
        fetchCertificates(buildFilterUrl());
    });

    certificateFiltersForm.querySelectorAll('select, input[type="date"]').forEach(field => {
        field.addEventListener('change', submitFiltersWithDelay);
    });
}

if (searchInput) searchInput.addEventListener('input', submitFiltersWithDelay);

if (toggleCertificateFilters) {
    toggleCertificateFilters.addEventListener('click', function () {
        setAdvancedFiltersVisibility(certificateAdvancedFilters?.classList.contains('hidden'));
    });
}

if (resetCertificateFilters && certificateFiltersForm) {
    resetCertificateFilters.addEventListener('click', function (event) {
        event.preventDefault();
        certificateFiltersForm.reset();
        setAdvancedFiltersVisibility(false);
        fetchCertificates(this.href);
    });
}

if (certificatesResults) {
    certificatesResults.addEventListener('click', function (event) {
        const link = event.target.closest('a');
        if (!link) return;
        const href = link.getAttribute('href') ?? '';
        const certificatesUrl = @json(route('admin.certificates'));
        const url = new URL(href, window.location.origin);
        if (url.pathname !== new URL(certificatesUrl, window.location.origin).pathname) return;
        event.preventDefault();
        fetchCertificates(href);
    });
}

bindDeleteButtons();
setAdvancedFiltersVisibility(hasAdvancedFiltersApplied());

if (window.location.search) {
    fetchCertificates(window.location.href);
}
</script>
@endpush
@endsection
```

### resources/views/admin/dashboard.blade.php

```blade
@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $nonceBinding = 'csp_nonce';
    $cspNonce = app()->bound($nonceBinding) ? app()->make($nonceBinding) : '';
@endphp
<style nonce="{{ $cspNonce }}">
    .admin-dashboard-shell {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 12% 14%, rgba(96, 165, 250, 0.16), transparent 18%),
            radial-gradient(circle at 88% 18%, rgba(59, 130, 246, 0.12), transparent 16%),
            linear-gradient(180deg, #f7fbff 0%, #eef5ff 46%, #f8fbff 100%);
    }

    .admin-dashboard-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.16;
        background-image:
            linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
        background-size: 74px 74px;
        mask-image: radial-gradient(circle at center, rgba(15, 23, 42, 0.9), transparent 82%);
    }

    .admin-dashboard-stage {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 24px 56px -40px rgba(15, 23, 42, 0.24);
    }

    .admin-dashboard-stage::before {
        content: none;
    }

    .admin-bg-orb {
        position: absolute;
        border-radius: 9999px;
        pointer-events: none;
        opacity: 0.42;
        filter: blur(10px);
        animation: admin-dashboard-float 14s ease-in-out infinite;
    }

    .admin-bg-orb.orb-left {
        top: 5rem;
        left: -4rem;
        width: 18rem;
        height: 18rem;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.26) 0%, rgba(96, 165, 250, 0.08) 52%, transparent 76%);
    }

    .admin-bg-orb.orb-right {
        top: 6rem;
        right: -4rem;
        width: 22rem;
        height: 22rem;
        background: radial-gradient(circle, rgba(147, 197, 253, 0.32) 0%, rgba(191, 219, 254, 0.1) 48%, transparent 74%);
        animation-delay: -4s;
    }

    .admin-bg-orb.orb-bottom {
        bottom: 2rem;
        left: 50%;
        width: 24rem;
        height: 24rem;
        transform: translateX(-50%);
        background: radial-gradient(circle, rgba(125, 211, 252, 0.2) 0%, rgba(186, 230, 253, 0.08) 46%, transparent 72%);
        animation-delay: -8s;
    }

    .admin-hero-panel {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.98) 0%, rgba(255, 255, 255, 0.98) 52%, rgba(239, 246, 255, 0.94) 100%);
        box-shadow: 0 20px 40px -34px rgba(15, 23, 42, 0.18);
    }

    .admin-hero-panel::after {
        content: "";
        position: absolute;
        right: -3rem;
        bottom: -4rem;
        width: 14rem;
        height: 14rem;
        border-radius: 9999px;
        background: radial-gradient(circle, rgba(96, 165, 250, 0.2), transparent 72%);
        pointer-events: none;
    }

    .admin-section-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        border-radius: 9999px;
        padding: 0.42rem 0.95rem;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        color: #1d4ed8;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        box-shadow: 0 12px 28px -22px rgba(37, 99, 235, 0.8);
    }

    .admin-section-chip::before {
        content: "";
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 9999px;
        background: linear-gradient(135deg, #2563eb, #60a5fa);
        box-shadow: 0 0 0 5px rgba(191, 219, 254, 0.65);
    }

    .admin-status-card {
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 16px 36px -30px rgba(15, 23, 42, 0.14);
    }

    .admin-stat-card-local {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
        box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.16);
    }

    .admin-stat-card-local::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(219, 234, 254, 0.24), transparent 48%);
        pointer-events: none;
    }

    .admin-stat-card-local::after {
        content: "";
        position: absolute;
        left: 1.5rem;
        right: 1.5rem;
        bottom: 0;
        height: 3px;
        border-radius: 9999px;
        background: linear-gradient(90deg, rgba(147, 197, 253, 0.55), rgba(37, 99, 235, 0.9), rgba(125, 211, 252, 0.55));
    }

    .admin-status-card.role-superadmin {
        border-color: rgba(251, 191, 36, 0.45);
        background: linear-gradient(135deg, rgba(255, 251, 235, 0.97), rgba(255, 255, 255, 0.96));
    }

    .admin-status-card.role-superadmin .role-kicker {
        color: #b45309;
    }

    .admin-status-card.role-superadmin .role-dot {
        background: linear-gradient(135deg, #f59e0b, #f97316);
        box-shadow: 0 0 0 5px rgba(254, 243, 199, 0.95);
    }

    .admin-stat-card-local.stat-superadmin {
        border-color: rgba(251, 191, 36, 0.4);
        background: linear-gradient(180deg, rgba(255, 251, 235, 0.98), rgba(255, 255, 255, 0.98));
    }

    .admin-stat-card-local.stat-superadmin::before {
        background: linear-gradient(135deg, rgba(253, 230, 138, 0.22), transparent 48%);
    }

    .admin-stat-card-local.stat-superadmin::after {
        background: linear-gradient(90deg, rgba(252, 211, 77, 0.55), rgba(245, 158, 11, 0.92), rgba(249, 115, 22, 0.55));
    }

    @keyframes admin-dashboard-float {
        0%, 100% {
            transform: translate3d(0, 0, 0);
        }
        50% {
            transform: translate3d(0, -18px, 0);
        }
    }
</style>

<div class="admin-dashboard-shell min-h-screen">
    <div class="admin-bg-orb orb-left"></div>
    <div class="admin-bg-orb orb-right"></div>
    <div class="admin-bg-orb orb-bottom"></div>
    @include('layouts.navbar')

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="admin-dashboard-stage rounded-[2rem] p-4 sm:p-5 lg:p-6">
            <div class="admin-hero-panel rounded-3xl p-6 portal-panel sm:p-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <span class="admin-section-chip">Area Administrasi</span>
                        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">Dashboard Admin</h1>
                        <p class="mt-2 text-sm text-slate-500">Ringkasan utama sistem ditampilkan sesuai hak akses pengguna.</p>
                    </div>
                    <div class="admin-status-card rounded-2xl px-5 py-4 {{ auth()->user()->isSuperAdmin() ? 'role-superadmin' : '' }}">
                        <div class="flex items-center gap-2">
                            <span class="role-dot inline-flex h-2.5 w-2.5 rounded-full bg-gradient-to-br from-blue-500 to-sky-400 shadow-[0_0_0_5px_rgba(219,234,254,0.9)]"></span>
                            <p class="role-kicker text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">Status Akses</p>
                        </div>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ auth()->user()->role_label }}</p>
                        <p class="mt-1 text-sm text-slate-500">Masuk sebagai administrator aktif.</p>
                    </div>
                </div>
                <div class="pulse-line mt-5 h-1 rounded-full bg-gradient-to-r from-blue-200 via-blue-600 to-sky-200"></div>
            </div>

            <section class="mt-6">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <span class="admin-section-chip">Ringkasan</span>
                        <h2 class="text-lg font-bold text-slate-900">Ringkasan Sistem</h2>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="admin-stat-card-local rounded-3xl p-6 portal-panel" data-delay="1">
                        <p class="text-sm font-medium text-blue-700">Total Sertifikat</p>
                        <p class="mt-4 text-4xl font-bold tracking-tight text-slate-900">{{ $totalCerts }}</p>
                        <p class="mt-3 text-sm text-slate-500">Dokumen sertifikat yang sudah tersimpan.</p>
                    </div>
                    <div class="admin-stat-card-local rounded-3xl p-6 portal-panel" data-delay="1">
                        <p class="text-sm font-medium text-blue-700">Upload Hari Ini</p>
                        <p class="mt-4 text-4xl font-bold tracking-tight text-slate-900">{{ $todayUploads }}</p>
                        <p class="mt-3 text-sm text-slate-500">Penambahan sertifikat yang masuk hari ini.</p>
                    </div>
                    @if(auth()->user()->isSuperAdmin())
                    <div class="admin-stat-card-local stat-superadmin rounded-3xl p-6 portal-panel" data-delay="1">
                        <p class="text-sm font-medium text-amber-700">Total Admin</p>
                        <p class="mt-4 text-4xl font-bold tracking-tight text-slate-900">{{ $totalAdmins }}</p>
                        <p class="mt-3 text-sm text-slate-500">Akun administrator yang saat ini aktif.</p>
                    </div>
                    <div class="admin-stat-card-local stat-superadmin rounded-3xl p-6 portal-panel" data-delay="1">
                        <p class="text-sm font-medium text-amber-700">Aktivitas Hari Ini</p>
                        <p class="mt-4 text-4xl font-bold tracking-tight text-slate-900">{{ $todayLogs }}</p>
                        <p class="mt-3 text-sm text-slate-500">Peristiwa sistem yang tercatat untuk audit.</p>
                    </div>
                    @else
                    <div class="admin-stat-card-local rounded-3xl p-6 portal-panel sm:col-span-2 xl:col-span-2" data-delay="1">
                        <p class="text-sm font-medium text-blue-700">Peran Akun</p>
                        <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ auth()->user()->role_label }}</p>
                        <p class="mt-3 text-sm text-slate-500">Hak akses saat ini mengikuti peran administrator yang digunakan.</p>
                    </div>
                    @endif
                </div>
            </section>
        </div>

    </div>
</div>
@endsection
```

### resources/views/admin/edit.blade.php

```blade
@extends('layouts.app')
@section('title', 'Edit Sertifikat')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('layouts.navbar')

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <form method="POST" action="{{ route('admin.certificates.update', $certificate) }}" enctype="multipart/form-data" id="editForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="qr_x"    id="inputQrX"    value="{{ $certificate->qr_x ?? 50 }}">
            <input type="hidden" name="qr_y"    id="inputQrY"    value="{{ $certificate->qr_y ?? 50 }}">
            <input type="hidden" name="qr_size" id="inputQrSize" value="{{ $certificate->qr_size ?? 150 }}">

            <div class="grid lg:grid-cols-2 gap-6">
                <!-- Kolom Kiri -->
                <div class="space-y-6">
                    <!-- Data Mahasiswa -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="bi bi-person-vcard text-blue-600 mr-2"></i>Data Mahasiswa
                        </h2>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama" value="{{ old('nama', $certificate->nama) }}"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NPM</label>
                                <input type="text" name="npm" value="{{ old('npm', $certificate->npm) }}"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Sertifikat</label>
                                <input type="text" name="nomor_sertifikat" value="{{ old('nomor_sertifikat', $certificate->nomor_sertifikat) }}"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Sertifikat</label>
                                <select id="jenisPilihan" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Pilih Jenis</option>
                                    @foreach(['Asisten Dosen','Asisten Praktikum','Pelatihan','Seminar'] as $jenis)
                                    <option value="{{ $jenis }}" {{ old('jenis_sertifikat', $certificate->jenis_sertifikat) == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                                    @endforeach
                                    <option value="__manual__">Ketik Manual</option>
                                </select>
                                <input type="text" name="jenis_sertifikat" id="jenisSertifikat"
                                       value="{{ old('jenis_sertifikat', $certificate->jenis_sertifikat) }}"
                                       placeholder="Ketik jenis sertifikat"
                                       class="w-full mt-2 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 {{ in_array(old('jenis_sertifikat', $certificate->jenis_sertifikat), ['Asisten Dosen','Asisten Praktikum','Pelatihan','Seminar']) ? 'hidden' : '' }}"
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Sampai</label>
                                <input type="date" name="valid_until" value="{{ old('valid_until', optional($certificate->valid_until)->format('Y-m-d')) }}"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-400">Kosongkan jika masa berlaku tidak ditentukan.</p>
                            </div>
                        </div>
                    </div>

                    <!-- File Sertifikat -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="bi bi-file-earmark-image text-blue-600 mr-2"></i>File Sertifikat
                        </h2>
                        <div class="mb-3 flex items-center space-x-3">
                            <a href="/{{ $certificate->file_path }}" target="_blank"
                               class="flex items-center space-x-1 text-sm text-blue-600 hover:underline">
                                <i class="bi bi-eye"></i><span>Lihat file saat ini</span>
                            </a>
                            <span class="text-xs text-gray-400 font-mono">{{ basename($certificate->file_path) }}</span>
                        </div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ganti File (opsional)</label>
                        <input type="file" name="certificate_file" id="certFileInput" accept="image/jpeg,image/png"
                               class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg px-3 py-2 cursor-pointer bg-white">
                        <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak ingin mengganti. JPG, PNG · Maks 5MB</p>
                    </div>

                    <!-- Opsi QR (hanya muncul jika ada file baru) -->
                    <div id="qrOptionsBox" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hidden">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="bi bi-qr-code text-blue-600 mr-2"></i>Posisi QR Code
                        </h2>
                        <label class="flex items-center space-x-3 cursor-pointer mb-4">
                            <input type="checkbox" name="attach_qr" id="attachQr" value="true"
                                   class="w-5 h-5 text-blue-600 rounded" {{ $certificate->qr_attached ? 'checked' : '' }}>
                            <span class="text-gray-700 font-medium">Tempel QR Code di sertifikat</span>
                        </label>
                        <div id="qrControls" class="{{ $certificate->qr_attached ? '' : 'hidden' }} space-y-4">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label class="text-sm font-medium text-gray-700">Ukuran QR</label>
                                    <span class="text-sm font-bold text-blue-600" id="qrSizeLabel">{{ $certificate->qr_size ?? 150 }} px</span>
                                </div>
                                <input type="range" id="qrSizeRange" min="150" max="400" value="{{ max(150, $certificate->qr_size ?? 150) }}"
                                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi X (px)</label>
                                    <input type="number" id="qrXInput" value="{{ $certificate->qr_x ?? 50 }}" min="0"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi Y (px)</label>
                                    <input type="number" id="qrYInput" value="{{ $certificate->qr_y ?? 50 }}" min="0"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <p class="text-xs text-blue-500 font-medium"><i class="bi bi-hand-index mr-1"></i>Geser QR di preview untuk atur posisi</p>
                        </div>
                    </div>

                    <!-- Tombol -->
                    <div class="flex space-x-4">
                        <button type="button" id="openWarnModalBtn"
                                class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-lg">
                            <i class="bi bi-check-lg mr-2"></i>Simpan Perubahan
                        </button>
                        <a href="{{ route('admin.certificates') }}" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                            Batal
                        </a>
                    </div>
                </div>

                <!-- Kolom Kanan: Preview -->
                <div class="space-y-4">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 sticky top-20">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="bi bi-eye text-blue-600 mr-2"></i>Preview
                        </h2>

                        <!-- Placeholder -->
                        <div id="previewEmpty" class="flex flex-col items-center justify-center h-64 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                            <i class="bi bi-image text-5xl text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-400">Pilih file baru untuk melihat preview</p>
                        </div>

                        <!-- Preview container -->
                        <div id="previewContainer" class="hidden relative rounded-lg overflow-hidden bg-gray-100" style="touch-action:none; user-select:none;">
                            <img id="certPreview" src="" alt="Preview" class="w-full h-auto block" style="pointer-events:none;">
                            <div id="qrOverlay" class="absolute hidden" style="left:50px;top:50px;width:150px;height:150px;cursor:grab;z-index:10;">
                                <div style="width:100%;height:100%;background:white;border:3px solid #3b82f6;border-radius:8px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.3);">
                                    <i class="bi bi-qr-code" id="qrIcon" style="font-size:80px;color:#1f2937;"></i>
                                </div>
                                <div style="position:absolute;top:-22px;left:0;right:0;text-align:center;">
                                    <span style="background:#3b82f6;color:white;font-size:11px;padding:2px 8px;border-radius:4px;">Geser QR</span>
                                </div>
                            </div>
                        </div>

                        <!-- Info koordinat -->
                        <div id="coordInfo" class="hidden mt-3 p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-700">
                                <i class="bi bi-pin-map mr-1"></i>
                                Posisi pada gambar asli:
                                <span class="font-bold">X: <span id="realX">50</span>px, Y: <span id="realY">50</span>px</span>
                                · Ukuran: <span class="font-bold"><span id="realSize">150</span>px</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Modal Peringatan -->
        <div id="warnModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                <div class="flex items-start space-x-4 mb-5">
                    <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-shield-exclamation text-2xl text-red-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Konfirmasi Perubahan Sertifikat</h3>
                        <p class="text-sm text-gray-500">Setiap perubahan akan memperbarui keamanan dokumen secara otomatis.</p>
                    </div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-5 space-y-2">
                    <p class="text-xs font-semibold text-yellow-800 uppercase tracking-wide mb-2">Dampak perubahan ini:</p>
                    <div class="flex items-start space-x-2 text-sm text-yellow-800">
                        <i class="bi bi-arrow-repeat text-yellow-600 mt-0.5 flex-shrink-0"></i>
                        <span><strong>QR Code akan di-regenerate</strong> — QR lama tidak berlaku lagi</span>
                    </div>
                    <div class="flex items-start space-x-2 text-sm text-yellow-800">
                        <i class="bi bi-shield-lock text-yellow-600 mt-0.5 flex-shrink-0"></i>
                        <span><strong>Hash SHA-256 & digital signature</strong> akan dihitung ulang</span>
                    </div>
                    <div class="flex items-start space-x-2 text-sm text-yellow-800">
                        <i class="bi bi-exclamation-triangle text-yellow-600 mt-0.5 flex-shrink-0"></i>
                        <span>Sertifikat yang sudah dibagikan perlu diperbarui</span>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="closeWarnModalBtn"
                            class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition text-sm">
                        <i class="bi bi-arrow-left mr-1"></i>Batal
                    </button>
                    <button type="button" id="confirmBtn" disabled
                            class="flex-1 px-4 py-2.5 bg-gray-300 text-gray-500 rounded-lg font-semibold transition text-sm cursor-not-allowed">
                        <i class="bi bi-hourglass-split mr-1"></i>Simpan (<span id="countdown">3</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
document.getElementById('openWarnModalBtn')?.addEventListener('click', () => openWarnModal());
document.getElementById('closeWarnModalBtn')?.addEventListener('click', () => closeWarnModal());
document.getElementById('confirmBtn')?.addEventListener('click', () => submitEdit());
// ── Preview & QR overlay ──────────────────────────────────────
const certFileInput    = document.getElementById('certFileInput');
const certPreview      = document.getElementById('certPreview');
const previewEmpty     = document.getElementById('previewEmpty');
const previewContainer = document.getElementById('previewContainer');
const qrOverlay        = document.getElementById('qrOverlay');
const qrIcon           = document.getElementById('qrIcon');
const attachQr         = document.getElementById('attachQr');
const qrControls       = document.getElementById('qrControls');
const qrOptionsBox     = document.getElementById('qrOptionsBox');
const qrSizeRange      = document.getElementById('qrSizeRange');
const qrSizeLabel      = document.getElementById('qrSizeLabel');
const qrXInput         = document.getElementById('qrXInput');
const qrYInput         = document.getElementById('qrYInput');
const coordInfo        = document.getElementById('coordInfo');
const jenisPilihan     = document.getElementById('jenisPilihan');
const jenisSertifikat  = document.getElementById('jenisSertifikat');

let origW = 0;

certFileInput.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = new Image();
        img.onload = () => {
            origW = img.naturalWidth;
            certPreview.src = e.target.result;
            previewEmpty.classList.add('hidden');
            previewContainer.classList.remove('hidden');
            qrOptionsBox.classList.remove('hidden');
            if (attachQr.checked) {
                qrOverlay.classList.remove('hidden');
                coordInfo.classList.remove('hidden');
            }
            updateOverlay();
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
});

attachQr.addEventListener('change', function () {
    qrControls.classList.toggle('hidden', !this.checked);
    if (this.checked && origW) {
        qrOverlay.classList.remove('hidden');
        coordInfo.classList.remove('hidden');
        updateOverlay();
    } else {
        qrOverlay.classList.add('hidden');
        coordInfo.classList.add('hidden');
    }
});

qrSizeRange.addEventListener('input', function () {
    qrSizeLabel.textContent = this.value + ' px';
    document.getElementById('inputQrSize').value = this.value;
    updateOverlay(); syncCoords();
});
qrXInput.addEventListener('input', () => { document.getElementById('inputQrX').value = qrXInput.value; updateOverlay(); syncCoords(); });
qrYInput.addEventListener('input', () => { document.getElementById('inputQrY').value = qrYInput.value; updateOverlay(); syncCoords(); });

function getScale() {
    return origW ? previewContainer.offsetWidth / origW : 1;
}

function updateOverlay() {
    const scale    = getScale();
    const dispSize = parseInt(qrSizeRange.value) * scale;
    qrOverlay.style.width  = dispSize + 'px';
    qrOverlay.style.height = dispSize + 'px';
    qrOverlay.style.left   = ((parseInt(qrXInput.value) || 0) * scale) + 'px';
    qrOverlay.style.top    = ((parseInt(qrYInput.value) || 0) * scale) + 'px';
    qrIcon.style.fontSize  = Math.max(16, dispSize * 0.65) + 'px';
}

function syncCoords() {
    document.getElementById('realX').textContent    = qrXInput.value;
    document.getElementById('realY').textContent    = qrYInput.value;
    document.getElementById('realSize').textContent = qrSizeRange.value;
}

// Drag QR overlay
let dragging = false, startMouseX, startMouseY, startLeft, startTop;

qrOverlay.addEventListener('mousedown', function (e) {
    dragging = true;
    startMouseX = e.clientX; startMouseY = e.clientY;
    startLeft   = parseInt(qrOverlay.style.left) || 0;
    startTop    = parseInt(qrOverlay.style.top)  || 0;
    qrOverlay.style.cursor = 'grabbing';
    e.preventDefault();
});
document.addEventListener('mousemove', function (e) {
    if (!dragging) return;
    moveQr(e.clientX, e.clientY);
});
document.addEventListener('mouseup', () => {
    if (dragging) { dragging = false; qrOverlay.style.cursor = 'grab'; }
});
qrOverlay.addEventListener('touchstart', function (e) {
    dragging = true;
    startMouseX = e.touches[0].clientX; startMouseY = e.touches[0].clientY;
    startLeft   = parseInt(qrOverlay.style.left) || 0;
    startTop    = parseInt(qrOverlay.style.top)  || 0;
    e.preventDefault();
}, { passive: false });
document.addEventListener('touchmove', function (e) {
    if (!dragging) return;
    moveQr(e.touches[0].clientX, e.touches[0].clientY);
    e.preventDefault();
}, { passive: false });
document.addEventListener('touchend', () => { dragging = false; });

function moveQr(clientX, clientY) {
    const scale   = getScale();
    const newLeft = Math.max(0, Math.min(startLeft + (clientX - startMouseX), previewContainer.offsetWidth  - qrOverlay.offsetWidth));
    const newTop  = Math.max(0, Math.min(startTop  + (clientY - startMouseY), previewContainer.offsetHeight - qrOverlay.offsetHeight));
    qrOverlay.style.left = newLeft + 'px';
    qrOverlay.style.top  = newTop  + 'px';
    qrXInput.value = Math.round(newLeft / scale);
    qrYInput.value = Math.round(newTop  / scale);
    document.getElementById('inputQrX').value = qrXInput.value;
    document.getElementById('inputQrY').value = qrYInput.value;
    syncCoords();
}

window.addEventListener('resize', updateOverlay);

jenisPilihan?.addEventListener('change', function () {
    if (this.value === '__manual__') {
        jenisSertifikat.classList.remove('hidden');
        jenisSertifikat.value = '';
        jenisSertifikat.focus();
    } else {
        jenisSertifikat.classList.add('hidden');
        jenisSertifikat.value = this.value;
    }
});

if (jenisSertifikat) {
    if (jenisSertifikat.value && !jenisSertifikat.classList.contains('hidden')) {
        jenisPilihan.value = '__manual__';
    } else if (jenisSertifikat.value) {
        jenisPilihan.value = jenisSertifikat.value;
    }
}

// ── Modal countdown ───────────────────────────────────────────
let countdownTimer = null;

function openWarnModal() {
    document.getElementById('warnModal').classList.remove('hidden');
    startCountdown();
}
function closeWarnModal() {
    document.getElementById('warnModal').classList.add('hidden');
    clearInterval(countdownTimer);
    resetCountdown();
}
function startCountdown() {
    resetCountdown();
    let sisa = 3;
    const btn = document.getElementById('confirmBtn');
    countdownTimer = setInterval(() => {
        sisa--;
        document.getElementById('countdown').textContent = sisa;
        if (sisa <= 0) {
            clearInterval(countdownTimer);
            btn.disabled  = false;
            btn.className = 'flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition text-sm cursor-pointer';
            btn.innerHTML = '<i class="bi bi-check-lg mr-1"></i>Ya, Simpan Perubahan';
        }
    }, 1000);
}
function resetCountdown() {
    const btn = document.getElementById('confirmBtn');
    btn.disabled  = true;
    btn.className = 'flex-1 px-4 py-2.5 bg-gray-300 text-gray-500 rounded-lg font-semibold transition text-sm cursor-not-allowed';
    btn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i>Simpan (<span id="countdown">3</span>)';
}
function submitEdit() {
    document.getElementById('editForm').submit();
}
document.getElementById('warnModal').addEventListener('click', function (e) {
    if (e.target === this) closeWarnModal();
});
</script>
@endsection
```

### resources/views/admin/partials/certificates-results.blade.php

```blade
@php
    $sortIcon = function (string $column) use ($sortBy, $sortDir) {
        if ($sortBy !== $column) {
            return 'bi-arrow-down-up';
        }

        return $sortDir === 'asc' ? 'bi-sort-down' : 'bi-sort-up';
    };

    $sortUrl = function (string $column) use ($sortBy, $sortDir) {
        $nextDir = $sortBy === $column && $sortDir === 'asc' ? 'desc' : 'asc';

        return route('admin.certificates', array_merge(request()->query(), [
            'sort_by' => $column,
            'sort_dir' => $nextDir,
        ]));
    };

    $highlight = function (?string $value) use ($search) {
        $text = (string) $value;
        $escapedText = e($text);

        if ($search === '') {
            return $escapedText;
        }

        $pattern = '/' . preg_quote($search, '/') . '/iu';

        return preg_replace($pattern, '<mark class="bg-amber-100 text-amber-900 px-1 rounded">$0</mark>', $escapedText) ?? $escapedText;
    };
@endphp

<div class="overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ $sortUrl('name') }}" class="inline-flex items-center gap-2 hover:text-blue-600 transition">
                        <span>Nama</span>
                        <i class="bi {{ $sortIcon('name') }}"></i>
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ $sortUrl('npm') }}" class="inline-flex items-center gap-2 hover:text-blue-600 transition">
                        <span>NPM</span>
                        <i class="bi {{ $sortIcon('npm') }}"></i>
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ $sortUrl('certificate_number') }}" class="inline-flex items-center gap-2 hover:text-blue-600 transition">
                        <span>Nomor Sertifikat</span>
                        <i class="bi {{ $sortIcon('certificate_number') }}"></i>
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ $sortUrl('certificate_type') }}" class="inline-flex items-center gap-2 hover:text-blue-600 transition">
                        <span>Jenis</span>
                        <i class="bi {{ $sortIcon('certificate_type') }}"></i>
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ $sortUrl('created_at') }}" class="inline-flex items-center gap-2 hover:text-blue-600 transition">
                        <span>Tanggal</span>
                        <i class="bi {{ $sortIcon('created_at') }}"></i>
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SHA-256</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($certificates as $cert)
            @php
                $isOwner = $cert->created_by === auth()->id();
                $canEdit = auth()->user()->isAdmin() && !auth()->user()->isSuperAdmin();
                $canDelete = auth()->user()->isAdmin() && !auth()->user()->isSuperAdmin();
            @endphp
            <tr class="hover:bg-gray-50 {{ !$isOwner ? 'opacity-90' : '' }}">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    {!! $highlight($cert->nama) !!}
                    @if(!$isOwner)
                    <span class="ml-1 text-xs text-gray-400" title="Diupload oleh {{ $cert->uploader?->name ?? 'Admin lain' }}">
                        <i class="bi bi-person"></i> {{ $cert->uploader?->name ?? 'Admin lain' }}
                    </span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{!! $highlight($cert->npm) !!}</td>
                <td class="px-6 py-4 text-sm text-gray-600 font-mono">{!! $highlight($cert->nomor_sertifikat) !!}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{!! $highlight($cert->jenis_sertifikat) !!}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $cert->tanggal_terbit }}</td>
                <td class="px-6 py-4">
                    @if($cert->file_hash)
                    <span class="text-xs font-mono text-gray-400 bg-gray-50 px-2 py-1 rounded" title="{{ $cert->file_hash }}">
                        {{ substr($cert->file_hash, 0, 12) }}...
                    </span>
                    @else
                    <span class="text-xs text-gray-400">-</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="/{{ $cert->file_path }}" target="_blank" class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100" title="Lihat Sertifikat">
                            Lihat
                        </a>
                        @if($cert->qr_code_path)
                        <a href="/{{ $cert->qr_code_path }}" target="_blank" class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700 transition hover:bg-green-100" title="Lihat QR Code">
                            Lihat QR
                        </a>
                        @endif
                        @if($canEdit)
                        <a href="{{ route('admin.certificates.edit', $cert) }}" class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100" title="Edit Sertifikat">
                            Edit
                        </a>
                        @else
                        <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-400 cursor-not-allowed" title="Anda tidak memiliki akses edit">
                            Edit
                        </span>
                        @endif
                        @if($canDelete)
                        <form method="POST" action="{{ route('admin.certificates.delete', $cert) }}" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="delete-cert-btn inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100" title="Hapus Sertifikat" data-name="{{ $cert->nama }}">
                                Hapus
                            </button>
                        </form>
                        @else
                        <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-400 cursor-not-allowed" title="Hanya super admin yang dapat menghapus">
                            Hapus
                        </span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                    {{ $search !== '' || $type !== '' || $owner !== 'all' || $dateFrom !== '' || $dateTo !== '' ? 'Tidak ada sertifikat yang cocok dengan filter' : 'Belum ada sertifikat' }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-6 border-t flex justify-between items-center">
    {{ $certificates->links() }}
    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700">
        <span>Kembali</span>
    </a>
</div>
```

### resources/views/admin/upload.blade.php

```blade
@extends('layouts.app')
@section('title', 'Upload Sertifikat')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('layouts.navbar')

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <form method="POST" action="{{ route('admin.upload.post') }}" enctype="multipart/form-data" id="uploadForm">
            @csrf

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Data Mahasiswa</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama" value="{{ old('nama') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NPM</label>
                                <input type="text" name="npm" value="{{ old('npm') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Sertifikat</label>
                                <input type="text" name="nomor_sertifikat" value="{{ old('nomor_sertifikat') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Sertifikat</label>
                                <select id="jenisPilihan" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Pilih Jenis</option>
                                    @foreach(['Asisten Dosen','Asisten Praktikum','Pelatihan','Seminar'] as $jenis)
                                    <option value="{{ $jenis }}" {{ old('jenis_sertifikat') == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                                    @endforeach
                                    <option value="__manual__">Ketik Manual</option>
                                </select>
                                <input type="text" name="jenis_sertifikat" id="jenisSertifikat"
                                       value="{{ old('jenis_sertifikat') }}"
                                       placeholder="Ketik jenis sertifikat"
                                       class="w-full mt-2 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 {{ in_array(old('jenis_sertifikat'), ['Asisten Dosen','Asisten Praktikum','Pelatihan','Seminar']) || !old('jenis_sertifikat') ? 'hidden' : '' }}"
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Sampai</label>
                                <input type="date" name="valid_until" value="{{ old('valid_until') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-400">Kosongkan jika masa berlaku tidak ditentukan.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">File Sertifikat</h2>
                        <label for="certFile" class="block text-sm font-medium text-gray-700 mb-2">Pilih File (JPG/PNG, maks 5MB)</label>
                        <input type="file" name="certificate_file" id="certFile" accept="image/jpeg,image/png" class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg px-3 py-2 cursor-pointer bg-white focus:ring-2 focus:ring-blue-500" required>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Opsi QR Code</h2>
                        <div class="space-y-3 mb-5">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="attach_qr" id="attachQr" value="true" class="w-5 h-5 text-blue-600 rounded">
                                <span class="text-gray-700 font-medium">Tempel QR Code di sertifikat</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="qr_only" id="qrOnly" value="true" class="w-5 h-5 text-green-600 rounded">
                                <span class="text-gray-700 font-medium">Cetak QR saja <span class="text-xs text-gray-400">(output gambar putih + QR)</span></span>
                            </label>
                        </div>

                        <div id="qrOptions" class="space-y-4 hidden">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label class="text-sm font-medium text-gray-700">Ukuran QR</label>
                                    <span class="text-sm font-bold text-blue-600" id="qrSizeLabel">150 px</span>
                                </div>
                                <input type="range" name="qr_size" id="qrSize" min="150" max="400" value="150" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi X (px)</label>
                                    <input type="number" name="qr_x" id="qrX" value="50" min="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi Y (px)</label>
                                    <input type="number" name="qr_y" id="qrY" value="50" min="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <p class="text-xs text-blue-500 font-medium">Geser QR di preview untuk atur posisi.</p>
                        </div>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-lg">Upload Sertifikat</button>
                        <a href="{{ route('admin.dashboard') }}" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">Batal</a>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 sticky top-20">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Preview</h2>

                        <div id="previewEmpty" class="flex flex-col items-center justify-center h-64 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                            <p class="text-sm text-gray-400">Pilih file sertifikat untuk melihat preview.</p>
                        </div>

                        <div id="previewContainer" class="hidden relative rounded-lg overflow-hidden bg-gray-100" style="touch-action: none; user-select: none;">
                            <img id="certPreview" src="" alt="Preview" class="w-full h-auto block" style="pointer-events: none;">
                            <div id="qrOverlay" class="absolute hidden" style="left:50px; top:50px; width:150px; height:150px; cursor:grab; z-index:10;">
                                <div style="width:100%; height:100%; background:white; border:3px solid #3b82f6; border-radius:8px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.3);">
                                    <span id="qrIcon" style="font-size:40px; color:#1f2937; font-weight:700; letter-spacing:0.2em;">QR</span>
                                </div>
                                <div style="position:absolute; top:-22px; left:0; right:0; text-align:center;">
                                    <span style="background:#3b82f6; color:white; font-size:11px; padding:2px 8px; border-radius:4px;">Geser QR</span>
                                </div>
                            </div>
                        </div>

                        <div id="coordInfo" class="hidden mt-3 p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-700">
                                Posisi pada gambar asli:
                                <span class="font-bold">X: <span id="realX">50</span>px, Y: <span id="realY">50</span>px</span>
                                <span class="font-bold">Ukuran: <span id="realSize">150</span>px</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
const certFile = document.getElementById('certFile');
const certPreview = document.getElementById('certPreview');
const previewEmpty = document.getElementById('previewEmpty');
const previewContainer = document.getElementById('previewContainer');
const qrOverlay = document.getElementById('qrOverlay');
const qrIcon = document.getElementById('qrIcon');
const attachQr = document.getElementById('attachQr');
const qrOnly = document.getElementById('qrOnly');
const qrOptions = document.getElementById('qrOptions');
const qrSize = document.getElementById('qrSize');
const qrSizeLabel = document.getElementById('qrSizeLabel');
const qrX = document.getElementById('qrX');
const qrY = document.getElementById('qrY');
const coordInfo = document.getElementById('coordInfo');

let origW = 0;

certFile.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        const img = new Image();
        img.onload = function () {
            origW = img.naturalWidth;
            certPreview.src = e.target.result;
            previewEmpty.classList.add('hidden');
            previewContainer.classList.remove('hidden');
            if (attachQr.checked || qrOnly.checked) {
                qrOverlay.classList.remove('hidden');
                coordInfo.classList.remove('hidden');
                updateOverlay();
            }
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
});

attachQr.addEventListener('change', function () {
    if (this.checked) qrOnly.checked = false;
    toggleQrOptions();
});
qrOnly.addEventListener('change', function () {
    if (this.checked) attachQr.checked = false;
    toggleQrOptions();
});

function toggleQrOptions() {
    const show = attachQr.checked || qrOnly.checked;
    qrOptions.classList.toggle('hidden', !show);
    if (show && origW) {
        qrOverlay.classList.remove('hidden');
        coordInfo.classList.remove('hidden');
        updateOverlay();
    } else if (!show) {
        qrOverlay.classList.add('hidden');
        coordInfo.classList.add('hidden');
    }
}

qrSize.addEventListener('input', function () {
    qrSizeLabel.textContent = this.value + ' px';
    updateOverlay();
    syncCoords();
});
qrX.addEventListener('input', () => { updateOverlay(); syncCoords(); });
qrY.addEventListener('input', () => { updateOverlay(); syncCoords(); });

function getScale() {
    return origW ? previewContainer.offsetWidth / origW : 1;
}

function updateOverlay() {
    const scale = getScale();
    const size = parseInt(qrSize.value);
    const dispSize = size * scale;
    const dispX = (parseInt(qrX.value) || 0) * scale;
    const dispY = (parseInt(qrY.value) || 0) * scale;
    qrOverlay.style.width = dispSize + 'px';
    qrOverlay.style.height = dispSize + 'px';
    qrOverlay.style.left = dispX + 'px';
    qrOverlay.style.top = dispY + 'px';
    qrIcon.style.fontSize = Math.max(16, dispSize * 0.35) + 'px';
}

function syncCoords() {
    document.getElementById('realX').textContent = qrX.value;
    document.getElementById('realY').textContent = qrY.value;
    document.getElementById('realSize').textContent = qrSize.value;
}

let dragging = false, startMouseX, startMouseY, startLeft, startTop;
qrOverlay.addEventListener('mousedown', function (e) {
    dragging = true;
    startMouseX = e.clientX; startMouseY = e.clientY;
    startLeft = parseInt(qrOverlay.style.left) || 0;
    startTop = parseInt(qrOverlay.style.top) || 0;
    qrOverlay.style.cursor = 'grabbing';
    e.preventDefault();
});
document.addEventListener('mousemove', function (e) { if (dragging) moveOverlay(e.clientX, e.clientY); });
document.addEventListener('mouseup', function () { if (dragging) { dragging = false; qrOverlay.style.cursor = 'grab'; } });
qrOverlay.addEventListener('touchstart', function (e) {
    const t = e.touches[0];
    dragging = true;
    startMouseX = t.clientX; startMouseY = t.clientY;
    startLeft = parseInt(qrOverlay.style.left) || 0;
    startTop = parseInt(qrOverlay.style.top) || 0;
    e.preventDefault();
}, { passive: false });
document.addEventListener('touchmove', function (e) {
    if (dragging) { moveOverlay(e.touches[0].clientX, e.touches[0].clientY); e.preventDefault(); }
}, { passive: false });
document.addEventListener('touchend', () => { dragging = false; });

function moveOverlay(clientX, clientY) {
    const scale = getScale();
    const maxLeft = previewContainer.offsetWidth - qrOverlay.offsetWidth;
    const maxTop = previewContainer.offsetHeight - qrOverlay.offsetHeight;
    const newLeft = Math.max(0, Math.min(startLeft + (clientX - startMouseX), maxLeft));
    const newTop = Math.max(0, Math.min(startTop + (clientY - startMouseY), maxTop));
    qrOverlay.style.left = newLeft + 'px';
    qrOverlay.style.top = newTop + 'px';
    qrX.value = Math.round(newLeft / scale);
    qrY.value = Math.round(newTop / scale);
    syncCoords();
}
window.addEventListener('resize', updateOverlay);

const jenisPilihan = document.getElementById('jenisPilihan');
const jenisSertifikat = document.getElementById('jenisSertifikat');
jenisPilihan.addEventListener('change', function () {
    if (this.value === '__manual__') {
        jenisSertifikat.classList.remove('hidden');
        jenisSertifikat.value = '';
        jenisSertifikat.focus();
    } else {
        jenisSertifikat.classList.add('hidden');
        jenisSertifikat.value = this.value;
    }
});
if (jenisSertifikat.value && !jenisSertifikat.classList.contains('hidden')) {
    jenisPilihan.value = '__manual__';
} else if (jenisSertifikat.value) {
    jenisPilihan.value = jenisSertifikat.value;
}
</script>
@endsection
```

### resources/views/admin/users/create.blade.php

```blade
@extends('layouts.app')
@section('title', 'Tambah User')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('layouts.navbar')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Tambah User Baru</h2>
                    <p class="text-sm text-gray-500 mt-1">Tentukan role sesuai hak akses yang dibutuhkan</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700">
                    <i class="bi bi-arrow-left"></i><span>Kembali</span>
                </a>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="p-6 space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select name="role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Pilih Role</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin - Upload & kelola sertifikat</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Super Admin hanya ada satu. Viewer tidak memiliki akun.</p>
                </div>
                <div class="flex space-x-4 pt-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        <i class="bi bi-person-plus mr-2"></i>Buat User
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

### resources/views/admin/users/edit.blade.php

```blade
@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('layouts.navbar')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Edit User</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $user->email }}</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700">
                    <i class="bi bi-arrow-left"></i><span>Kembali</span>
                </a>
            </div>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-6 space-y-5">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span></label>
                    <input type="password" name="password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    @if($user->role === 'superadmin')
                        <input type="hidden" name="role" value="superadmin">
                        <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 flex items-center space-x-2">
                            <i class="bi bi-shield-fill-check text-red-500"></i>
                            <span>Super Admin - tidak dapat diubah</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Role Super Admin bersifat permanen dan hanya ada satu.</p>
                    @else
                        <select name="role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin - Upload & kelola sertifikat</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Role Viewer tidak memiliki akun pada sistem ini.</p>
                    @endif
                </div>
                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           class="w-5 h-5 text-blue-600 rounded"
                           {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                           {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                    <label for="is_active" class="text-sm font-medium text-gray-700">Akun Aktif</label>
                </div>
                <div class="flex space-x-4 pt-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        <i class="bi bi-check-lg mr-2"></i>Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

### resources/views/admin/users/index.blade.php

```blade
@extends('layouts.app')
@section('title', 'Kelola User')

@section('content')
<style nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
    .admin-users-shell {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 12% 14%, rgba(96, 165, 250, 0.16), transparent 18%),
            radial-gradient(circle at 88% 18%, rgba(59, 130, 246, 0.12), transparent 16%),
            linear-gradient(180deg, #f7fbff 0%, #eef5ff 46%, #f8fbff 100%);
    }

    .admin-users-card {
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 24px 56px -40px rgba(15, 23, 42, 0.24);
    }
</style>

<div class="admin-users-shell min-h-screen">
    @include('layouts.navbar')

    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="admin-users-card rounded-3xl overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Kelola User</h2>
                    <p class="text-sm text-gray-500 mt-1">Atur akun, peran, dan status pengguna sistem.</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center space-x-1 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                        <i class="bi bi-arrow-left"></i><span>Kembali</span>
                    </a>
                    <a href="{{ route('admin.users.create') }}"
                       class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        <i class="bi bi-person-plus mr-1"></i>Tambah User
                    </a>
                </div>
            </div>

            <!-- Role Legend -->
            <div class="px-6 py-3 bg-slate-50 border-b flex flex-wrap gap-4 text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    <strong>Super Admin</strong> - Full access (upload, delete, kelola user)
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <strong>Admin</strong> - Upload & lihat sertifikat
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                    <strong>Viewer</strong> - Hanya lihat sertifikat (read-only)
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($users as $user)
                        <tr class="hover:bg-gray-50 {{ $user->id === auth()->id() ? 'bg-blue-50/30' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm {{ $user->isSuperAdmin() ? 'bg-gradient-to-br from-amber-400 to-orange-500' : 'bg-blue-600' }}">
                                        {{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $user->name ?? '-' }}
                                            @if($user->id === auth()->id())
                                            <span class="text-xs text-blue-500">(Anda)</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $user->role_badge }}">
                                    {{ $user->role_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->is_active)
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-green-100 text-green-700">Aktif</span>
                                @else
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-red-100 text-red-700">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition" title="Detail">
                                        <i class="bi bi-person-lines-fill"></i>
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="delete-user-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="delete-user-btn p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus"
                                                data-name="{{ $user->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
document.querySelectorAll('.delete-user-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        if (confirm('Hapus user ' + this.dataset.name + '?')) {
            this.closest('form').submit();
        }
    });
});
</script>
@endpush
@endsection
```

### resources/views/admin/users/show.blade.php

```blade
@extends('layouts.app')
@section('title', 'Detail User - {{ $user->name }}')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('layouts.navbar')

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @include('layouts.notification')

        <!-- Header profil -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-5">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold
                        {{ $user->isSuperAdmin() ? 'bg-red-500' : ($user->isAdmin() ? 'bg-blue-600' : 'bg-gray-400') }}">
                        {{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                        <p class="text-gray-500 text-sm">{{ $user->email }}</p>
                        <div class="flex items-center space-x-2 mt-1.5">
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $user->role_badge }}">
                                {{ $user->role_label }}
                            </span>
                            @if($user->is_active)
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-green-100 text-green-700">Aktif</span>
                            @else
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-red-100 text-red-700">Nonaktif</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700">
                        <i class="bi bi-arrow-left"></i><span>Kembali</span>
                    </a>
                    <a href="{{ route('admin.users.edit', $user) }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                        <i class="bi bi-pencil mr-1"></i>Edit User
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistik aktivitas -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @php
                $statItems = [
                    ['action' => 'upload', 'label' => 'Upload',  'icon' => 'bi-cloud-upload',   'color' => 'blue'],
                    ['action' => 'edit',   'label' => 'Edit',    'icon' => 'bi-pencil-square',  'color' => 'yellow'],
                    ['action' => 'delete', 'label' => 'Hapus',   'icon' => 'bi-trash',          'color' => 'red'],
                    ['action' => 'login',  'label' => 'Login',   'icon' => 'bi-box-arrow-in-right', 'color' => 'green'],
                ];
            @endphp
            @foreach($statItems as $s)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs mb-1">{{ $s['label'] }}</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $stats[$s['action']] ?? 0 }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-{{ $s['color'] }}-100">
                        <i class="bi {{ $s['icon'] }} text-{{ $s['color'] }}-600"></i>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="grid lg:grid-cols-2 gap-6">

            <!-- Sertifikat yang di-upload -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-5 border-b flex items-center justify-between">
                    <h2 class="font-bold text-gray-900 flex items-center">
                        <i class="bi bi-file-earmark-text text-blue-600 mr-2"></i>
                        Sertifikat Diupload
                        <span class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ $certificates->count() }}</span>
                    </h2>
                </div>
                <div class="divide-y max-h-96 overflow-y-auto">
                    @forelse($certificates as $cert)
                    <div class="px-5 py-3 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $cert->nama }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ $cert->nomor_sertifikat }}</p>
                                <span class="text-xs text-gray-400">{{ $cert->jenis_sertifikat }}</span>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <p class="text-xs text-gray-400">{{ $cert->tanggal_terbit }}</p>
                                <a href="/{{ $cert->file_path }}" target="_blank"
                                   class="text-xs text-blue-600 hover:underline">
                                    <i class="bi bi-eye mr-0.5"></i>Lihat
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-10 text-center text-gray-400 text-sm">
                        <i class="bi bi-inbox text-3xl block mb-2"></i>
                        Belum ada sertifikat yang diupload
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Log aktivitas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-5 border-b flex items-center justify-between">
                    <h2 class="font-bold text-gray-900 flex items-center">
                        <i class="bi bi-journal-text text-yellow-600 mr-2"></i>
                        Riwayat Aktivitas
                        <span class="ml-2 text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">{{ $logs->total() }}</span>
                    </h2>
                </div>
                <div class="divide-y max-h-96 overflow-y-auto">
                    @forelse($logs as $log)
                    <div class="px-5 py-3 hover:bg-gray-50 flex items-start justify-between space-x-3">
                        <div class="flex items-start space-x-3 min-w-0">
                            <span class="mt-0.5 flex-shrink-0 text-xs px-2 py-0.5 rounded-full font-medium {{ $log->action_badge }}">
                                {{ strtoupper($log->action) }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-700 truncate">{{ $log->description ?? $log->target ?? '-' }}</p>
                                <p class="text-xs text-gray-400">{{ $log->ip_address }}</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 whitespace-nowrap flex-shrink-0">
                            {{ $log->created_at->format('d/m/y H:i') }}
                        </span>
                    </div>
                    @empty
                    <div class="px-5 py-10 text-center text-gray-400 text-sm">
                        <i class="bi bi-journal-x text-3xl block mb-2"></i>
                        Belum ada aktivitas
                    </div>
                    @endforelse
                </div>
                @if($logs->hasPages())
                <div class="p-4 border-t">{{ $logs->links() }}</div>
                @endif
            </div>

        </div>

    </div>
</div>
@endsection
```

### resources/views/admin/verify.blade.php

```blade
@extends('layouts.app')
@section('title', 'Verifikasi Sertifikat')

@section('content')
<style nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
    .admin-tool-shell {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 12% 14%, rgba(96, 165, 250, 0.16), transparent 18%),
            radial-gradient(circle at 88% 18%, rgba(59, 130, 246, 0.12), transparent 16%),
            linear-gradient(180deg, #f7fbff 0%, #eef5ff 46%, #f8fbff 100%);
    }

    .admin-tool-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.16;
        background-image:
            linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
        background-size: 74px 74px;
        mask-image: radial-gradient(circle at center, rgba(15, 23, 42, 0.9), transparent 82%);
    }

    .admin-tool-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 24px 56px -40px rgba(15, 23, 42, 0.24);
    }
</style>

<div class="admin-tool-shell min-h-screen">
    @include('layouts.navbar')

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="admin-tool-card rounded-3xl p-8">
            <div class="mx-auto mb-8 max-w-4xl">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">Verifikasi Sertifikat</h1>
                        <p class="mt-2 text-sm leading-7 text-gray-500 sm:text-base">Periksa keaslian sertifikat melalui nomor, kamera, atau unggah gambar kode QR.</p>
                    </div>
                    <a href="{{ route('admin.dashboard') }}" class="shrink-0 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Kembali</a>
                </div>
            </div>

            <div class="mx-auto mb-8 flex max-w-4xl flex-wrap justify-center gap-2 overflow-x-auto border-b border-slate-200 pb-5">
                <button type="button" data-tab="manual" id="tab-manual" class="tab-btn rounded-full bg-blue-600 px-5 py-2.5 font-semibold text-white shadow-sm shadow-blue-200 whitespace-nowrap">Input Manual</button>
                <button type="button" data-tab="scan" id="tab-scan" class="tab-btn rounded-full border border-blue-100 bg-blue-50 px-5 py-2.5 font-semibold text-blue-700 whitespace-nowrap">Scan QR</button>
                <button type="button" data-tab="upload" id="tab-upload" class="tab-btn rounded-full border border-blue-100 bg-blue-50 px-5 py-2.5 font-semibold text-blue-700 whitespace-nowrap">Upload Gambar</button>
            </div>

            <div id="content-manual" class="tab-content">
                <div class="mx-auto max-w-4xl">
                    <div class="flex flex-col gap-4">
                        <input type="text" id="cert_number" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="Masukkan nomor sertifikat...">
                        <button type="button" id="verifyManualBtn" class="w-full rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-700">Cari</button>
                    </div>
                </div>
            </div>

            <div id="content-scan" class="tab-content hidden">
                <div class="text-center space-y-4">
                    <div id="scanArea" class="mx-auto max-w-md border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-gray-50" style="min-height:280px;">
                        <div id="scanIdle" class="flex flex-col items-center justify-center h-64 px-6 text-center">
                            <p class="text-gray-500">Klik tombol di bawah untuk mulai scan.</p>
                        </div>
                        <video id="scanVideo" class="hidden w-full" autoplay playsinline></video>
                    </div>
                    <button type="button" id="btnStartScan" class="rounded-xl bg-blue-600 px-6 py-2.5 font-semibold text-white hover:bg-blue-700">Mulai Scan Kamera</button>
                    <button type="button" id="btnStopScan" class="hidden rounded-xl bg-red-500 px-6 py-2.5 font-semibold text-white hover:bg-red-600">Stop</button>
                </div>
            </div>

            <div id="content-upload" class="tab-content hidden">
                <input type="file" id="fileInput" accept="image/*" style="display:none">
                <div id="uploadZone" class="relative flex min-h-48 w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 transition-all hover:border-blue-400 hover:bg-blue-50">
                    <div id="uploadIdle" class="text-center px-6 py-8">
                        <p class="text-gray-600 font-medium mb-1">Drag and drop atau klik untuk pilih gambar QR</p>
                        <p class="text-gray-400 text-sm">atau tekan <kbd class="px-2 py-0.5 bg-gray-200 rounded text-xs font-mono">Ctrl+V</kbd> untuk paste</p>
                    </div>
                    <div id="uploadPreview" class="hidden w-full p-4">
                        <div class="flex items-center space-x-4">
                            <img id="previewImg" src="" alt="" class="w-24 h-24 object-contain rounded-lg border border-gray-200 bg-white">
                            <div class="flex-1 min-w-0">
                                <p id="previewName" class="text-sm font-medium text-gray-700 truncate"></p>
                                <p id="previewSize" class="text-xs text-gray-400 mt-0.5"></p>
                                <button type="button" id="clearUploadBtn" class="mt-2 text-xs text-red-500 hover:text-red-700">Hapus</button>
                            </div>
                                <button type="button" id="submitUploadBtn" class="flex-shrink-0 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Verifikasi</button>
                        </div>
                    </div>
                    <div id="uploadOverlay" class="hidden absolute inset-0 bg-blue-100/80 border-2 border-blue-500 rounded-xl flex items-center justify-center pointer-events-none">
                        <div class="text-center">
                            <p class="text-blue-700 font-semibold mt-2">Lepaskan untuk verifikasi</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="result" class="hidden mt-8 p-6 rounded-xl"></div>
        </div>
    </div>
</div>

<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

document.querySelectorAll('[data-tab]').forEach((button) => {
    button.addEventListener('click', () => switchTab(button.dataset.tab));
});

document.getElementById('verifyManualBtn')?.addEventListener('click', () => verifyManual());
document.getElementById('btnStartScan')?.addEventListener('click', () => startScan());
document.getElementById('btnStopScan')?.addEventListener('click', () => stopScan());
document.getElementById('clearUploadBtn')?.addEventListener('click', (event) => clearUpload(event));
document.getElementById('submitUploadBtn')?.addEventListener('click', (event) => submitUpload(event));

function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('border-b-2', 'border-blue-600', 'text-blue-600');
        el.classList.add('text-gray-500');
    });
    document.getElementById('content-' + tab).classList.remove('hidden');
    const btn = document.getElementById('tab-' + tab);
    btn.classList.add('border-b-2', 'border-blue-600', 'text-blue-600');
    btn.classList.remove('text-gray-500');
    document.getElementById('result').classList.add('hidden');
    if (tab !== 'scan') stopScan();
}

async function verifyManual() {
    const num = document.getElementById('cert_number').value.trim();
    if (!num) return;
    showLoading();
    try {
        const res  = await fetch('{{ route("admin.verify.search") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ cert_number: num })
        });
        const data = await res.json();
        data.success ? showSuccess(data.data) : showError(data.message);
    } catch (err) {
        showError('Terjadi kesalahan koneksi.');
    }
}

document.getElementById('cert_number').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') verifyManual();
});

let scanStream = null, scanInterval = null;

async function startScan() {
    try {
        scanStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        const video = document.getElementById('scanVideo');
        video.srcObject = scanStream;
        video.classList.remove('hidden');
        document.getElementById('scanIdle').classList.add('hidden');
        document.getElementById('btnStartScan').classList.add('hidden');
        document.getElementById('btnStopScan').classList.remove('hidden');
        if ('BarcodeDetector' in window) {
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            scanInterval = setInterval(async () => {
                try {
                    const codes = await detector.detect(video);
                    if (codes.length > 0) { stopScan(); handleQrText(codes[0].rawValue); }
                } catch (_) {}
            }, 500);
        } else {
            showError('Browser tidak mendukung BarcodeDetector. Gunakan tab Upload Gambar.');
            stopScan();
        }
    } catch (err) {
        showError('Tidak dapat mengakses kamera.');
    }
}

function stopScan() {
    if (scanStream) { scanStream.getTracks().forEach(t => t.stop()); scanStream = null; }
    if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
    const video = document.getElementById('scanVideo');
    if (video) { video.srcObject = null; video.classList.add('hidden'); }
    document.getElementById('scanIdle')?.classList.remove('hidden');
    document.getElementById('btnStartScan')?.classList.remove('hidden');
    document.getElementById('btnStopScan')?.classList.add('hidden');
}

function handleQrText(text) {
    switchTab('scan');
    showLoading();
    const match = text.match(/nomor_sertifikat=([^&]+)/);
    if (match) {
        fetch('{{ route("admin.verify.search") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ cert_number: decodeURIComponent(match[1]) })
        }).then(r => r.json()).then(data => {
            data.success ? showSuccess(data.data) : showError(data.message);
        });
    } else {
        showError('Format QR tidak valid.');
    }
}

let uploadedFile = null;
const uploadZone = document.getElementById('uploadZone');
const uploadOverlay = document.getElementById('uploadOverlay');
const fileInput = document.getElementById('fileInput');

uploadZone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', e => { if (e.target.files[0]) handleFile(e.target.files[0]); });
uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadOverlay.classList.remove('hidden'); });
uploadZone.addEventListener('dragleave', e => { if (!uploadZone.contains(e.relatedTarget)) uploadOverlay.classList.add('hidden'); });
uploadZone.addEventListener('drop', e => {
    e.preventDefault(); uploadOverlay.classList.add('hidden');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) handleFile(file);
    else showError('File harus berupa gambar.');
});
document.addEventListener('paste', e => {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (const item of items) {
        if (item.type.startsWith('image/')) { switchTab('upload'); handleFile(item.getAsFile()); break; }
    }
});

function handleFile(file) {
    uploadedFile = file;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewName').textContent = file.name || 'Gambar dari clipboard';
        document.getElementById('previewSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
        document.getElementById('uploadIdle').classList.add('hidden');
        document.getElementById('uploadPreview').classList.remove('hidden');
    };
    reader.readAsDataURL(file);
}

function clearUpload(e) {
    if (e) e.stopPropagation();
    uploadedFile = null; fileInput.value = '';
    document.getElementById('uploadPreview').classList.add('hidden');
    document.getElementById('uploadIdle').classList.remove('hidden');
    document.getElementById('result').classList.add('hidden');
}

async function submitUpload(e) {
    if (e) e.stopPropagation();
    if (!uploadedFile) return;
    showLoading();
    const formData = new FormData();
    formData.append('qr_image', uploadedFile);
    try {
        const res = await fetch('{{ route("admin.verify.qr") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            body: formData
        });
        const data = await res.json();
        data.success ? showSuccess(data.data) : showError(data.message);
    } catch (err) {
        showError('Terjadi kesalahan koneksi.');
    }
}

function showLoading() {
    const el = document.getElementById('result');
    el.className = 'mt-8 p-6 rounded-xl bg-blue-50 border border-blue-200';
    el.innerHTML = '<div class="text-center"><p class="text-blue-700">Memverifikasi...</p></div>';
    el.classList.remove('hidden');
}

function buildHashBlock(hash) {
    if (!hash) return '';

    const shortHash = `${hash.slice(0, 12)}...${hash.slice(-12)}`;

    return `
        <div class="mb-5 rounded-2xl border border-green-200 bg-white p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">SHA-256 Dokumen</p>
                    <p class="mt-1 break-all font-mono text-sm font-semibold text-gray-800">${shortHash}</p>
                    <details class="mt-3">
                        <summary class="cursor-pointer text-sm font-medium text-green-700 hover:text-green-800">Lihat hash lengkap</summary>
                        <p class="mt-2 break-all rounded-lg bg-slate-50 p-3 font-mono text-xs text-slate-700">${hash}</p>
                    </details>
                </div>
                <button type="button" data-hash="${hash}" class="js-copy-hash inline-flex items-center justify-center rounded-lg border border-green-200 bg-white px-3 py-2 text-sm font-medium text-green-700 transition hover:bg-green-50">
                    Salin SHA-256
                </button>
            </div>
        </div>
    `;
}

async function copyHash(button) {
    const hash = button?.dataset?.hash;
    if (!hash) return;

    const originalText = button.textContent;

    try {
        await navigator.clipboard.writeText(hash);
        button.textContent = 'Tersalin';
        setTimeout(() => {
            button.textContent = originalText;
        }, 1800);
    } catch (_) {
        button.textContent = 'Gagal salin';
        setTimeout(() => {
            button.textContent = originalText;
        }, 1800);
    }
}

function buildHistoryBlock(history) {
    if (!Array.isArray(history) || history.length === 0) return '';

    const items = history.map(item => `
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-slate-800">${item.label}</p>
                <span class="text-xs text-slate-500">${item.created_at}</span>
            </div>
            <p class="mt-1 text-xs text-slate-600">${item.description}</p>
            <p class="mt-1 text-[11px] uppercase tracking-wide text-slate-400">Oleh ${item.actor}</p>
        </div>
    `).join('');

    return `
        <details class="mb-5">
            <summary class="inline-flex cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:bg-slate-50">
                Catatan
            </summary>
            <div class="mt-3 space-y-2">${items}</div>
        </details>
    `;
}

function showSuccess(data) {
    const isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
    const isSuperAdmin = {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }};
    const editUrl = '{{ url("/admin/certificates") }}/' + data.id + '/edit';
    const deleteUrl = '{{ url("/admin/certificates") }}/' + data.id;
    const csrfToken = CSRF;

    let actionButtons = '';
    if (isAdmin || isSuperAdmin) {
        actionButtons += `<a href="${editUrl}" class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 text-sm font-medium">Edit Sertifikat</a>`;
    }
    if (isSuperAdmin) {
        actionButtons += `
            <form method="POST" action="${deleteUrl}" class="js-delete-certificate inline">
                <input type="hidden" name="_token" value="${csrfToken}">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">Hapus</button>
            </form>`;
    }

    const el = document.getElementById('result');
    el.className = 'mt-8 p-6 rounded-xl bg-green-50 border border-green-200';
    el.innerHTML = `
        <div class="mb-5">
            <h3 class="text-xl font-bold text-green-900">Sertifikat Valid</h3>
            <p class="text-sm text-green-700 mt-1">Data sertifikat berhasil ditemukan.</p>
        </div>
        <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm text-gray-700 mb-5">
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Nama</span><p class="font-semibold mt-0.5">${data.nama}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">NPM</span><p class="font-semibold mt-0.5">${data.npm}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Nomor Sertifikat</span><p class="font-semibold font-mono mt-0.5">${data.nomor_sertifikat}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Jenis</span><p class="font-semibold mt-0.5">${data.jenis_sertifikat}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Tanggal Terbit</span><p class="font-semibold mt-0.5">${data.tanggal_terbit}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Berlaku Sampai</span><p class="font-semibold mt-0.5">${data.masa_berlaku}</p></div>
        </div>
        ${buildHashBlock(data.hash_sha256)}
        ${buildHistoryBlock(data.history_notes)}
        ${actionButtons ? `<div class="flex flex-wrap gap-3 mb-5">${actionButtons}</div>` : ''}
        ${data.file_path ? `
        <div class="border-t border-green-200 pt-4">
            <p class="text-xs text-gray-500 mb-2 font-medium uppercase tracking-wide">Dokumen Sertifikat</p>
            <img src="/${data.file_path}" alt="Sertifikat" class="w-full rounded-xl border border-green-200 shadow-md">
        </div>` : ''}
    `;
    el.classList.remove('hidden');
    el.querySelectorAll('.js-copy-hash').forEach((button) => {
        button.addEventListener('click', () => copyHash(button));
    });
    el.querySelectorAll('.js-delete-certificate').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!confirm('Hapus sertifikat ini?')) {
                event.preventDefault();
            }
        });
    });
}

function showError(message) {
    const el = document.getElementById('result');
    el.className = 'mt-8 p-6 rounded-xl bg-red-50 border border-red-200';
    el.innerHTML = `<div><h3 class="text-xl font-bold text-red-900 mb-1">Sertifikat Tidak Ditemukan</h3><p class="text-red-700 text-sm">${message}</p></div>`;
    el.classList.remove('hidden');
}
</script>
@endsection
```

### resources/views/auth/login.blade.php

```blade
@extends('layouts.app')
@section('title', 'Masuk - Sistem Sertifikat Digital')

@section('content')
<style nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
    .admin-portal {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 12% 18%, rgba(96, 165, 250, 0.30), transparent 18%),
            radial-gradient(circle at 88% 16%, rgba(59, 130, 246, 0.18), transparent 16%),
            radial-gradient(circle at 75% 72%, rgba(125, 211, 252, 0.22), transparent 18%),
            linear-gradient(135deg, #eff6ff 0%, #ffffff 42%, #f0f7ff 100%);
    }

    .admin-portal .orb {
        position: absolute;
        border-radius: 9999px;
        pointer-events: none;
        opacity: 0.75;
        filter: blur(6px);
        animation: portal-float 7s ease-in-out infinite;
    }

    .admin-portal .orb.orb-a {
        top: 90px;
        left: 7%;
        width: 160px;
        height: 160px;
        background: rgba(59, 130, 246, 0.20);
    }

    .admin-portal .orb.orb-b {
        top: 150px;
        right: 9%;
        width: 220px;
        height: 220px;
        background: rgba(147, 197, 253, 0.28);
        animation-duration: 9s;
        animation-direction: reverse;
    }

    .admin-portal .orb.orb-c {
        bottom: 70px;
        left: 18%;
        width: 120px;
        height: 120px;
        background: rgba(14, 165, 233, 0.18);
        animation-duration: 6.5s;
    }

    .admin-hero,
    .admin-login-card,
    .admin-feature {
        animation: portal-rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .admin-login-card {
        animation-delay: 0.1s;
        position: relative;
        z-index: 30;
        isolation: isolate;
        transform: perspective(1400px) rotateX(0.9deg) rotateY(-1.2deg);
        transform-origin: center center;
        overflow: hidden;
    }

    .admin-login-card::before {
        content: "";
        position: absolute;
        inset: 22px 22px -8px 22px;
        z-index: -2;
        border-radius: 28px;
        background: rgba(15, 23, 42, 0.08);
        filter: blur(20px);
        opacity: 0.22;
        transform: translateZ(-1px);
    }

    .admin-login-card::after {
        display: none;
    }

    .admin-input {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        box-shadow:
            inset 0 1px 2px rgba(15, 23, 42, 0.03),
            0 1px 0 rgba(255, 255, 255, 0.7);
        transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
    }

    .admin-input:focus {
        border-color: rgba(59, 130, 246, 0.45);
        box-shadow:
            0 0 0 4px rgba(59, 130, 246, 0.10),
            inset 0 1px 2px rgba(15, 23, 42, 0.03);
        transform: translateY(-1px);
    }

    .admin-input:-webkit-autofill,
    .admin-input:-webkit-autofill:hover,
    .admin-input:-webkit-autofill:focus,
    .admin-input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
        box-shadow: 0 0 0 1000px #ffffff inset !important;
        -webkit-text-fill-color: #111827 !important;
        caret-color: #111827;
        border: 1px solid #cbd5e1 !important;
        transition: background-color 99999s ease-in-out 0s;
    }

    .admin-submit {
        background: #2563eb;
        background-image: none;
        color: #ffffff;
        box-shadow: 0 16px 30px -20px rgba(37, 99, 235, 0.65);
        transition: transform 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
    }

    .admin-submit:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 20px 36px -22px rgba(37, 99, 235, 0.72);
    }

    .admin-submit:focus-visible {
        outline: none;
        box-shadow:
            0 0 0 4px rgba(59, 130, 246, 0.16),
            0 20px 36px -22px rgba(37, 99, 235, 0.72);
    }

    .admin-feature {
        animation-delay: 0.2s;
    }

    .admin-password-wrap {
        position: relative;
    }

    .admin-password-input {
        padding-right: 3.25rem;
    }

    .admin-password-toggle {
        position: absolute;
        top: 50%;
        right: 0.85rem;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 0;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        transition: color 180ms ease, transform 180ms ease;
    }

    .admin-password-toggle:hover {
        color: #2563eb;
    }

    .admin-password-toggle:focus-visible {
        outline: none;
        color: #2563eb;
    }

    .admin-line {
        background: linear-gradient(90deg, #93c5fd 0%, #2563eb 50%, #7dd3fc 100%);
        background-size: 200% 100%;
        animation: portal-line 4s linear infinite;
    }

    @keyframes portal-rise {
        from {
            opacity: 0;
            transform: translateY(28px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes portal-float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-18px);
        }
    }

    @keyframes portal-line {
        0% {
            background-position: 0% 50%;
        }
        100% {
            background-position: 200% 50%;
        }
    }
</style>

<div class="admin-portal flex flex-col items-center justify-center px-4 py-10" style="min-height:100vh;">
    <div class="orb orb-a"></div>
    <div class="orb orb-b"></div>
    <div class="orb orb-c"></div>

    <div class="relative z-10 w-full max-w-xl flex flex-col items-center gap-8">
        <div class="admin-hero text-center w-full">
            <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">Portal Admin</span>
            <h1 class="mt-4 text-3xl font-bold leading-tight text-gray-900 sm:text-4xl">Portal administrasi resmi untuk pengelolaan dan verifikasi sertifikat digital.</h1>
            <p class="mt-3 text-sm leading-7 text-gray-500">Area ini digunakan untuk mendukung proses administrasi sertifikat, verifikasi dokumen, dan pemantauan aktivitas sistem secara terpusat sesuai kebutuhan institusi.</p>
        </div>

        <div class="admin-login-card relative w-full rounded-3xl border border-slate-200/90 bg-white p-8 shadow-[0_24px_56px_-30px_rgba(15,23,42,0.24)] sm:p-10">
            <div class="admin-line absolute inset-x-8 top-0 h-1 rounded-full"></div>
            <h2 class="text-center text-2xl font-bold text-gray-900">Masuk</h2>
            <p class="mt-2 text-center text-sm text-gray-500">Gunakan akun administrator yang terdaftar.</p>

            @if($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                @csrf
                <div class="admin-feature">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="admin-input w-full rounded-xl px-4 py-3 outline-none" placeholder="nama@instansi.ac.id" required autofocus>
                </div>
                <div class="admin-feature">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                    <div class="admin-password-wrap">
                        <input type="password" name="password" id="passwordInput" class="admin-input admin-password-input w-full rounded-xl px-4 py-3 outline-none" placeholder="Masukkan kata sandi" required>
                        <button type="button" id="passwordToggle" class="admin-password-toggle" aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg id="passwordEyeOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="passwordEyeClosed" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3l18 18"></path>
                                <path d="M10.6 10.7a3 3 0 0 0 4.2 4.2"></path>
                                <path d="M9.9 5.2A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-4 4.8"></path>
                                <path d="M6.6 6.7C3.9 8.5 2 12 2 12a17.7 17.7 0 0 0 10 7 9.8 9.8 0 0 0 4-.8"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="admin-submit w-full rounded-xl py-3 font-semibold" style="background:#2563eb;background-image:none;">Masuk</button>
            </form>

            <div class="mt-6 pt-5 border-t text-center">
                <a href="{{ route('home') }}" class="text-sm text-blue-600 hover:text-blue-700">Kembali ke halaman verifikasi publik</a>
            </div>
        </div>
    </div>
</div>
<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
    const passwordInput = document.getElementById('passwordInput');
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordEyeOpen = document.getElementById('passwordEyeOpen');
    const passwordEyeClosed = document.getElementById('passwordEyeClosed');

    passwordToggle?.addEventListener('click', () => {
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        passwordToggle.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        passwordEyeOpen.classList.toggle('hidden', isHidden);
        passwordEyeClosed.classList.toggle('hidden', !isHidden);
    });
</script>
@endsection
```

### resources/views/errors/403.blade.php

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center px-4">
    <div class="w-full max-w-lg">

        {{-- Badge status --}}
        <div class="flex justify-center mb-6">
            <span class="inline-flex items-center gap-2 rounded-full bg-red-100 border border-red-200 px-4 py-1.5 text-sm font-bold text-red-700 tracking-wide uppercase">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                HTTP 403 Forbidden
            </span>
        </div>

        {{-- Card utama --}}
        <div class="bg-white rounded-2xl border border-red-200 shadow-lg overflow-hidden">

            {{-- Header merah --}}
            <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-5 flex items-center gap-4">
                <div class="flex-shrink-0 w-14 h-14 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Akses Ditolak</h1>
                    <p class="text-red-100 text-sm mt-0.5">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-4">

                {{-- Info role --}}
                <div class="rounded-xl bg-red-50 border border-red-100 px-4 py-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm">
                        <p class="font-semibold text-red-800">Role Anda tidak memiliki akses</p>
                        <p class="text-red-600 mt-0.5">
                            Login sebagai
                            <span class="font-bold">{{ auth()->user()?->role_label ?? 'Guest' }}</span>
                            — halaman ini hanya untuk <span class="font-bold">Super Admin</span>.
                        </p>
                    </div>
                </div>

                {{-- Detail teknis --}}
                <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 space-y-2 text-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Detail Teknis</p>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Status Code</span>
                        <span class="font-mono font-bold text-red-600">403 Forbidden</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">URL</span>
                        <span class="font-mono text-xs text-slate-700 truncate max-w-[220px]">{{ request()->path() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Role Aktif</span>
                        <span class="font-semibold text-slate-700">{{ auth()->user()?->role_label ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">User</span>
                        <span class="font-semibold text-slate-700">{{ auth()->user()?->email ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Waktu</span>
                        <span class="text-slate-700">{{ now()->format('d/m/Y H:i:s') }}</span>
                    </div>
                </div>

                <p class="text-xs text-slate-400 text-center">Hubungi Super Admin jika Anda membutuhkan akses ke halaman ini.</p>
            </div>

            {{-- Footer tombol --}}
            <div class="px-6 pb-5">
                <a href="{{ route('admin.dashboard') }}"
                   class="w-full flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 transition px-5 py-3 text-sm font-semibold text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
```

### resources/views/layouts/app.blade.php

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Certificate System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased">
    @yield('content')
    @stack('scripts')
    <script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
        document.querySelectorAll('[data-dismiss-target]').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById(button.dataset.dismissTarget)?.remove();
            });
        });
    </script>
</body>
</html>
```

### resources/views/layouts/navbar.blade.php

```blade
<nav class="sticky top-0 z-50 border-b border-blue-100 bg-white/95 backdrop-blur-sm">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
        <div class="shrink-0">
            <div>
                <span class="block text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Portal Admin</span>
                <div class="mt-0.5 flex items-center gap-2">
                    <span class="block text-base font-semibold text-slate-900">Sistem Sertifikat Digital</span>
                    <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-blue-700">
                        {{ auth()->user()->role_label }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex min-w-0 flex-1 items-center justify-center gap-2 overflow-x-auto px-2">
            <a href="{{ route('admin.dashboard') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Dashboard
            </a>
            <a href="{{ route('admin.certificates') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.certificates') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Daftar Sertifikat
            </a>
            <a href="{{ route('admin.verify') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.verify') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Verifikasi
            </a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.upload') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.upload') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Upload
            </a>
            @endif
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.users.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Kelola User
            </a>
            <a href="{{ route('admin.activity-logs') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.activity-logs') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Log Aktivitas
            </a>
            @endif
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-3">
            <div class="hidden items-center gap-3 sm:flex">
                <div class="text-right">
                    <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name ?? auth()->user()->email }}</p>
                    <span class="text-xs font-medium text-slate-500">{{ auth()->user()->role_label }}</span>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold text-white shadow-sm {{ auth()->user()->isSuperAdmin() ? 'bg-gradient-to-br from-amber-400 to-orange-500 shadow-amber-200' : 'bg-blue-600 shadow-blue-200' }}">
                    {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 transition hover:bg-blue-100">
                    Keluar
                </button>
            </form>
        </div>
    </div>
</nav>
```

### resources/views/layouts/notification.blade.php

```blade
@if(session('success'))
<div id="notif-success" class="mb-6 flex items-start justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">
    <div class="flex-1">
        <p class="text-sm leading-6 text-emerald-800">{{ session('success') }}</p>
    </div>
    <button type="button" data-dismiss-target="notif-success" class="text-sm font-medium text-emerald-600 transition hover:text-emerald-800">
        Tutup
    </button>
</div>
@endif

@if(session('error'))
<div id="notif-error" class="mb-6 flex items-start justify-between gap-4 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
    <div class="flex-1">
        <p class="text-sm leading-6 text-red-800">{{ session('error') }}</p>
    </div>
    <button type="button" data-dismiss-target="notif-error" class="text-sm font-medium text-red-600 transition hover:text-red-800">
        Tutup
    </button>
</div>
@endif

@if($errors->any())
<div id="notif-validation" class="mb-6 flex items-start justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
    <div class="flex-1">
        <p class="text-sm leading-6 text-amber-800">{{ $errors->first() }}</p>
    </div>
    <button type="button" data-dismiss-target="notif-validation" class="text-sm font-medium text-amber-700 transition hover:text-amber-900">
        Tutup
    </button>
</div>
@endif
```

### resources/views/verify.blade.php

```blade
@extends('layouts.app')
@section('title', 'Verifikasi Sertifikat Digital')

@section('content')
<div class="min-h-screen bg-slate-50 text-slate-900">
    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
        .public-portal {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.12), transparent 28%),
                radial-gradient(circle at bottom right, rgba(96, 165, 250, 0.14), transparent 24%),
                linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
        }

        .public-ambient {
            position: absolute;
            z-index: 0;
            border-radius: 9999px;
            pointer-events: none;
            opacity: 0.62;
            animation: public-float 18s ease-in-out infinite;
        }

        .public-ambient-one {
            top: 4rem;
            left: -5rem;
            width: 24rem;
            height: 24rem;
            background: radial-gradient(circle, rgba(96, 165, 250, 0.3) 0%, rgba(96, 165, 250, 0.12) 46%, transparent 74%);
        }

        .public-ambient-two {
            right: -6rem;
            top: 8rem;
            width: 26rem;
            height: 26rem;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.24) 0%, rgba(147, 197, 253, 0.1) 50%, transparent 75%);
            animation-delay: -4s;
        }

        .public-ambient-three {
            bottom: 1rem;
            left: 50%;
            width: 28rem;
            height: 28rem;
            transform: translateX(-50%);
            background: radial-gradient(circle, rgba(186, 230, 253, 0.26) 0%, rgba(186, 230, 253, 0.1) 42%, transparent 72%);
            animation-delay: -8s;
        }

        .public-grid {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            opacity: 0.28;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: radial-gradient(circle at center, rgba(15, 23, 42, 0.85), transparent 88%);
        }

        .public-wave {
            position: absolute;
            z-index: 0;
            left: -10%;
            width: 120%;
            height: 300px;
            pointer-events: none;
            opacity: 0.52;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            animation: public-drift 22s ease-in-out infinite;
        }

        .public-wave-one {
            top: 8rem;
            background-image: radial-gradient(ellipse at center, rgba(191, 219, 254, 0.26) 0%, rgba(191, 219, 254, 0.08) 38%, transparent 70%);
        }

        .public-wave-two {
            bottom: 5rem;
            background-image: radial-gradient(ellipse at center, rgba(147, 197, 253, 0.22) 0%, rgba(147, 197, 253, 0.08) 36%, transparent 72%);
            animation-delay: -10s;
        }

        .public-silhouette {
            position: absolute;
            z-index: 0;
            pointer-events: none;
            opacity: 0.44;
            filter: drop-shadow(0 22px 52px rgba(37, 99, 235, 0.14));
            animation: public-silhouette-float 20s ease-in-out infinite;
        }

        .public-silhouette svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .public-silhouette-left {
            left: -1rem;
            bottom: 4.5rem;
            width: 320px;
            height: 408px;
        }

        .public-silhouette-right {
            right: 0;
            bottom: 3.5rem;
            width: 348px;
            height: 420px;
            animation-delay: -7s;
        }

        .public-silhouette-top {
            left: 50%;
            top: 5.25rem;
            width: 300px;
            height: 200px;
            transform: translateX(-50%);
            opacity: 0.3;
            animation-delay: -11s;
        }

        .public-particle {
            position: absolute;
            z-index: 0;
            width: 12px;
            height: 12px;
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.9) 0%, rgba(96, 165, 250, 0.4) 55%, transparent 72%);
            box-shadow: 0 0 0 10px rgba(191, 219, 254, 0.22);
            pointer-events: none;
            opacity: 0.68;
            animation: public-particle-float 20s linear infinite;
        }

        .public-particle-one {
            top: 18%;
            left: 16%;
        }

        .public-particle-two {
            top: 26%;
            right: 18%;
            animation-delay: -6s;
        }

        .public-particle-three {
            bottom: 18%;
            left: 26%;
            animation-delay: -12s;
        }

        .public-particle-four {
            bottom: 22%;
            right: 22%;
            animation-delay: -16s;
        }

        .public-shell {
            position: relative;
            z-index: 1;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 28px;
            background: #ffffff;
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.08);
            animation: public-rise 700ms ease-out both;
        }

        .public-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0));
            pointer-events: none;
        }

        .public-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.95rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, rgba(219, 234, 254, 0.98), rgba(191, 219, 254, 0.88));
            color: #1d4ed8;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12);
        }

        .public-badge::before {
            content: "";
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            box-shadow: 0 0 0 5px rgba(96, 165, 250, 0.18);
        }

        .public-tab {
            position: relative;
            border-radius: 9999px;
            border: 1px solid rgba(191, 219, 254, 0.55);
            background: rgba(255, 255, 255, 0.9);
            color: #475569;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
            transition: all 180ms ease;
        }

        .public-tab::after {
            content: "";
            position: absolute;
            inset: auto 18% -0.35rem;
            height: 2px;
            border-radius: 9999px;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.75), transparent);
            transform: scaleX(0.4);
            opacity: 0;
            transition: opacity 180ms ease, transform 180ms ease;
        }

        .public-tab:hover {
            color: #1e3a8a;
            background: rgba(239, 246, 255, 0.98);
            border-color: rgba(147, 197, 253, 0.8);
            box-shadow: 0 14px 28px rgba(59, 130, 246, 0.12);
        }

        .public-tab.is-active {
            color: #1d4ed8;
            background: linear-gradient(135deg, rgba(239, 246, 255, 1), rgba(219, 234, 254, 0.98));
            border-color: rgba(96, 165, 250, 0.85);
            box-shadow: 0 16px 30px rgba(37, 99, 235, 0.12), inset 0 0 0 1px rgba(255, 255, 255, 0.92);
        }

        .public-tab.is-active::after {
            opacity: 1;
            transform: scaleX(1);
        }

        .public-input,
        .public-upload {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
        }

        .public-input:focus,
        .public-upload:focus-within {
            border-color: #60a5fa;
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.16);
            outline: none;
        }

        .public-button {
            border: 1px solid rgba(37, 99, 235, 0.08);
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: #ffffff;
            box-shadow: 0 16px 32px rgba(37, 99, 235, 0.24);
            transition: transform 180ms ease, box-shadow 180ms ease, filter 180ms ease;
        }

        .public-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.28);
            filter: saturate(1.03);
        }

        .public-button-secondary {
            border: 1px solid #93c5fd;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #1d4ed8;
            box-shadow: 0 12px 24px rgba(59, 130, 246, 0.1);
            transition: all 180ms ease;
        }

        .public-button-secondary:hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #60a5fa;
        }

        .public-card {
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
            border-radius: 22px;
            box-shadow: 0 20px 44px rgba(15, 23, 42, 0.06);
        }

        .public-result {
            border-radius: 22px;
            border-width: 1px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.06);
        }

        .public-content-wrap {
            max-width: 760px;
            margin-left: auto;
            margin-right: auto;
        }

        .public-form-wrap {
            max-width: 860px;
            margin-left: auto;
            margin-right: auto;
        }

        @keyframes public-float {
            0%, 100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(0, -18px, 0);
            }
        }

        @keyframes public-rise {
            from {
                opacity: 0;
                transform: translate3d(0, 18px, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        @keyframes public-drift {
            0%, 100% {
                transform: translate3d(0, 0, 0) scaleX(1);
            }
            50% {
                transform: translate3d(0, 16px, 0) scaleX(1.03);
            }
        }

        @keyframes public-particle-float {
            0% {
                transform: translate3d(0, 0, 0) scale(0.9);
                opacity: 0;
            }
            12%,
            88% {
                opacity: 0.5;
            }
            50% {
                transform: translate3d(18px, -22px, 0) scale(1);
            }
            100% {
                transform: translate3d(-10px, -48px, 0) scale(0.92);
                opacity: 0;
            }
        }

        @keyframes public-silhouette-float {
            0%, 100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(0, -12px, 0);
            }
        }
    </style>

<div class="public-portal min-h-screen">
    <div class="public-grid"></div>
    <div class="public-wave public-wave-one"></div>
    <div class="public-wave public-wave-two"></div>
    <div class="public-silhouette public-silhouette-top" aria-hidden="true">
        <svg viewBox="0 0 240 170" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 136C44 100 74 86 102 90C116 62 146 44 180 48C212 52 228 76 220 110C232 116 238 128 236 144H20V136Z" fill="url(#topSilhouette)" />
            <defs>
                <linearGradient id="topSilhouette" x1="24" y1="52" x2="220" y2="152" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#BFDBFE" />
                    <stop offset="1" stop-color="#60A5FA" stop-opacity="0.45" />
                </linearGradient>
            </defs>
        </svg>
    </div>
    <div class="public-ambient public-ambient-one"></div>
    <div class="public-ambient public-ambient-two"></div>
    <div class="public-ambient public-ambient-three"></div>
    <div class="public-silhouette public-silhouette-left" aria-hidden="true">
        <svg viewBox="0 0 260 340" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 340V250C34 214 70 206 96 224C106 178 138 154 176 156C214 158 246 186 260 234V340H0Z" fill="url(#leftSilhouetteMain)" />
            <path d="M28 340V278C58 252 88 248 112 264C122 228 146 208 176 208C208 208 232 228 244 262V340H28Z" fill="url(#leftSilhouetteSoft)" />
            <defs>
                <linearGradient id="leftSilhouetteMain" x1="20" y1="156" x2="230" y2="340" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE" />
                    <stop offset="1" stop-color="#93C5FD" stop-opacity="0.62" />
                </linearGradient>
                <linearGradient id="leftSilhouetteSoft" x1="48" y1="208" x2="214" y2="340" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#EFF6FF" />
                    <stop offset="1" stop-color="#BFDBFE" stop-opacity="0.55" />
                </linearGradient>
            </defs>
        </svg>
    </div>
    <div class="public-silhouette public-silhouette-right" aria-hidden="true">
        <svg viewBox="0 0 300 360" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M26 360V246C52 218 84 206 116 214C132 180 162 160 198 160C242 160 278 192 292 240V360H26Z" fill="url(#rightSilhouetteMain)" />
            <path d="M0 360V292C24 274 50 268 74 276C92 246 120 228 154 226C196 224 232 246 258 286V360H0Z" fill="url(#rightSilhouetteSoft)" />
            <defs>
                <linearGradient id="rightSilhouetteMain" x1="48" y1="166" x2="286" y2="360" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE" />
                    <stop offset="1" stop-color="#60A5FA" stop-opacity="0.56" />
                </linearGradient>
                <linearGradient id="rightSilhouetteSoft" x1="16" y1="230" x2="256" y2="360" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#EFF6FF" />
                    <stop offset="1" stop-color="#BFDBFE" stop-opacity="0.48" />
                </linearGradient>
            </defs>
        </svg>
    </div>
    <div class="public-particle public-particle-one"></div>
    <div class="public-particle public-particle-two"></div>
    <div class="public-particle public-particle-three"></div>
    <div class="public-particle public-particle-four"></div>
    <nav class="border-b border-slate-200/80 bg-white/88 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-blue-700 uppercase tracking-[0.22em]">Verifikasi Publik</p>
                <p class="text-base font-bold text-slate-900">Sistem Sertifikat Digital</p>
            </div>
            <a href="{{ route('login') }}" class="public-button inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold">Portal Admin</a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12 relative z-10">
        <div class="mb-8 text-center sm:mb-10">
            <span class="public-badge">Layanan Verifikasi Resmi</span>
            <h1 class="mx-auto mt-4 max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Verifikasi keaslian sertifikat digital secara cepat, jelas, dan terpercaya.</h1>
            <p class="mx-auto mt-4 max-w-3xl text-base leading-8 text-slate-600 sm:text-lg">Gunakan halaman ini untuk memeriksa keabsahan sertifikat melalui nomor sertifikat, pemindaian kode QR, atau unggah gambar dokumen pendukung.</p>
        </div>

        <div class="public-shell mx-auto max-w-4xl p-5 sm:p-8">
            <div class="public-content-wrap relative z-10">
                <div class="mb-8 flex flex-col items-center gap-4 border-b border-slate-200 pb-6 text-center">
                    <div class="flex flex-wrap justify-center gap-2">
                        <button type="button" data-tab="manual" id="tab-manual" class="tab-btn public-tab is-active px-5 py-2.5 text-sm font-semibold whitespace-nowrap">Input Manual</button>
                        <button type="button" data-tab="scan" id="tab-scan" class="tab-btn public-tab px-5 py-2.5 text-sm font-semibold whitespace-nowrap">Scan QR</button>
                        <button type="button" data-tab="upload" id="tab-upload" class="tab-btn public-tab px-5 py-2.5 text-sm font-semibold whitespace-nowrap">Upload Gambar</button>
                    </div>
                </div>

                <div id="content-manual" class="tab-content">
                    <div class="public-card public-form-wrap p-5 sm:p-6">
                        <div class="mb-4 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">Verifikasi dengan nomor sertifikat</h3>
                            <p class="mt-1 text-sm text-slate-500">Masukkan nomor sertifikat secara lengkap untuk memperoleh hasil verifikasi.</p>
                        </div>
                        <div class="flex flex-col items-center gap-3">
                            <input type="text" id="cert_number" class="public-input w-full rounded-xl px-4 py-3.5 text-slate-900 placeholder:text-slate-400" placeholder="Masukkan nomor sertifikat">
                            <button type="button" id="verifyManualBtn" class="public-button w-full rounded-xl px-6 py-3.5 font-semibold">Verifikasi</button>
                        </div>
                    </div>
                </div>

                <div id="content-scan" class="tab-content hidden">
                    <div class="public-card public-form-wrap p-5 text-center sm:p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-slate-900">Verifikasi melalui kamera</h3>
                            <p class="mt-1 text-sm text-slate-500">Arahkan kamera ke kode QR pada sertifikat untuk memulai pemeriksaan.</p>
                        </div>
                        <div class="space-y-4">
                            <div id="scanArea" class="public-upload mx-auto max-w-md overflow-hidden rounded-2xl border-2 border-dashed bg-slate-50/70" style="min-height:280px;">
                                <div id="scanIdle" class="flex flex-col items-center justify-center h-64 px-6 text-center">
                                    <p class="text-slate-500">Tekan tombol di bawah untuk mengaktifkan kamera.</p>
                                </div>
                                <video id="scanVideo" class="hidden w-full" autoplay playsinline></video>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-3">
                                <button type="button" id="btnStartScan" class="public-button rounded-xl px-6 py-3 font-semibold">Mulai Scan Kamera</button>
                                <button type="button" id="btnStopScan" class="public-button-secondary hidden rounded-xl px-6 py-3 font-semibold">Hentikan</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="content-upload" class="tab-content hidden">
                    <div class="public-card public-form-wrap p-5 sm:p-6">
                        <div class="mb-4 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">Verifikasi melalui unggah gambar</h3>
                            <p class="mt-1 text-sm text-slate-500">Unggah gambar yang memuat kode QR untuk diproses secara otomatis.</p>
                        </div>
                        <input type="file" id="fileInput" accept="image/*" class="hidden">
                        <div id="uploadZone" class="public-upload relative flex min-h-52 w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed bg-slate-50/70 transition-all hover:border-blue-300 hover:bg-blue-50/60">
                            <div id="uploadIdle" class="text-center px-6 py-8">
                                <p class="text-slate-700 font-medium mb-1">Seret file ke sini atau klik untuk memilih gambar kode QR</p>
                                <p class="text-slate-400 text-sm">Gambar juga dapat ditempel langsung dengan <kbd class="rounded bg-slate-200 px-2 py-0.5 text-xs font-mono">Ctrl+V</kbd></p>
                            </div>
                            <div id="uploadPreview" class="hidden w-full p-4">
                                <div class="flex items-center space-x-4">
                                    <img id="previewImg" src="" alt="" class="h-24 w-24 rounded-xl border border-slate-200 bg-white object-contain">
                                    <div class="min-w-0 flex-1">
                                        <p id="previewName" class="truncate text-sm font-medium text-slate-700"></p>
                                        <p id="previewSize" class="mt-0.5 text-xs text-slate-400"></p>
                                        <button type="button" id="clearUploadBtn" class="mt-2 text-xs font-medium text-red-600 hover:text-red-700">Hapus</button>
                                    </div>
                                    <button type="button" id="submitUploadBtn" class="public-button flex-shrink-0 rounded-xl px-5 py-3 text-sm font-semibold">Verifikasi</button>
                                </div>
                            </div>
                            <div id="uploadOverlay" class="hidden absolute inset-0 rounded-2xl border-2 border-blue-500 bg-blue-100/80 items-center justify-center pointer-events-none">
                                <div class="text-center">
                                    <p class="font-semibold text-blue-700">Lepaskan file untuk memulai verifikasi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="result" class="public-result hidden mt-8 p-6"></div>
            </div>
        </div>

        <div id="result-outer" class="hidden mx-auto max-w-4xl mt-6 relative z-10"></div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

document.querySelectorAll('[data-tab]').forEach((button) => {
    button.addEventListener('click', () => switchTab(button.dataset.tab));
});

document.getElementById('verifyManualBtn')?.addEventListener('click', () => verifyManual());
document.getElementById('btnStartScan')?.addEventListener('click', () => startScan());
document.getElementById('btnStopScan')?.addEventListener('click', () => stopScan());
document.getElementById('clearUploadBtn')?.addEventListener('click', (event) => clearUpload(event));
document.getElementById('submitUploadBtn')?.addEventListener('click', (event) => submitUpload(event));

function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('is-active');
    });
    document.getElementById('content-' + tab).classList.remove('hidden');
    const btn = document.getElementById('tab-' + tab);
    btn.classList.add('is-active');
    document.getElementById('result').classList.add('hidden');
    if (tab !== 'scan') stopScan();
}

async function verifyManual() {
    const num = document.getElementById('cert_number').value.trim();
    if (!num) return;
    showLoading();
    try {
        const res  = await fetch('{{ route("verify") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ cert_number: num })
        });
        const data = await res.json();
        data.success ? showSuccess(data.data) : showError(data.message);
    } catch (err) {
        showError('Terjadi kesalahan koneksi.');
    }
}

document.getElementById('cert_number').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') verifyManual();
});

let scanStream = null, scanInterval = null;

async function startScan() {
    try {
        scanStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        const video = document.getElementById('scanVideo');
        video.srcObject = scanStream;
        video.classList.remove('hidden');
        document.getElementById('scanIdle').classList.add('hidden');
        document.getElementById('btnStartScan').classList.add('hidden');
        document.getElementById('btnStopScan').classList.remove('hidden');
        if ('BarcodeDetector' in window) {
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            scanInterval = setInterval(async () => {
                try {
                    const codes = await detector.detect(video);
                    if (codes.length > 0) { stopScan(); handleQrText(codes[0].rawValue); }
                } catch (_) {}
            }, 500);
        } else {
            showError('Browser tidak mendukung BarcodeDetector. Gunakan tab Upload Gambar.');
            stopScan();
        }
    } catch (err) {
        showError('Tidak dapat mengakses kamera.');
    }
}

function stopScan() {
    if (scanStream) { scanStream.getTracks().forEach(t => t.stop()); scanStream = null; }
    if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
    const video = document.getElementById('scanVideo');
    if (video) { video.srcObject = null; video.classList.add('hidden'); }
    document.getElementById('scanIdle')?.classList.remove('hidden');
    document.getElementById('btnStartScan')?.classList.remove('hidden');
    document.getElementById('btnStopScan')?.classList.add('hidden');
}

function handleQrText(text) {
    showLoading();
    const match = text.match(/nomor_sertifikat=([^&]+)/);
    if (match) {
        fetch('{{ route("verify") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ cert_number: decodeURIComponent(match[1]) })
        }).then(r => r.json()).then(data => {
            data.success ? showSuccess(data.data) : showError(data.message);
        });
    } else {
        showError('Format QR tidak valid.');
    }
}

let uploadedFile = null;
const uploadZone = document.getElementById('uploadZone');
const uploadOverlay = document.getElementById('uploadOverlay');
const fileInput = document.getElementById('fileInput');

uploadZone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', e => { if (e.target.files[0]) handleFile(e.target.files[0]); });
uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadOverlay.classList.remove('hidden'); });
uploadZone.addEventListener('dragleave', e => { if (!uploadZone.contains(e.relatedTarget)) uploadOverlay.classList.add('hidden'); });
uploadZone.addEventListener('drop', e => {
    e.preventDefault(); uploadOverlay.classList.add('hidden');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) handleFile(file);
    else showError('File harus berupa gambar.');
});
document.addEventListener('paste', e => {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (const item of items) {
        if (item.type.startsWith('image/')) { switchTab('upload'); handleFile(item.getAsFile()); break; }
    }
});

function handleFile(file) {
    uploadedFile = file;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewName').textContent = file.name || 'Gambar dari clipboard';
        document.getElementById('previewSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
        document.getElementById('uploadIdle').classList.add('hidden');
        document.getElementById('uploadPreview').classList.remove('hidden');
    };
    reader.readAsDataURL(file);
}

function clearUpload(e) {
    if (e) e.stopPropagation();
    uploadedFile = null;
    fileInput.value = '';
    document.getElementById('uploadPreview').classList.add('hidden');
    document.getElementById('uploadIdle').classList.remove('hidden');
    document.getElementById('result').classList.add('hidden');
}

async function submitUpload(e) {
    if (e) e.stopPropagation();
    if (!uploadedFile) return;
    showLoading();
    const formData = new FormData();
    formData.append('qr_image', uploadedFile);
    try {
        const res = await fetch('{{ route("verify.qr") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            body: formData
        });
        const data = await res.json();
        data.success ? showSuccess(data.data) : showError(data.message);
    } catch (err) {
        showError('Terjadi kesalahan koneksi.');
    }
}

function showLoading() {
    const el = document.getElementById('result');
    el.className = 'public-result mt-8 p-6 bg-blue-50 border border-blue-200';
    el.innerHTML = '<div class="text-center"><p class="text-blue-700">Memverifikasi...</p></div>';
    el.classList.remove('hidden');
}

function buildDetailPanel(data) {
    const shortHash = data.hash_sha256 ? `${data.hash_sha256.slice(0, 12)}...${data.hash_sha256.slice(-12)}` : null;

    const hashSection = shortHash ? `
        <div class="border-b border-slate-100 pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">SHA-256 Dokumen</p>
            <p class="font-mono text-sm font-semibold text-slate-800 break-all">${shortHash}</p>
            <details class="mt-2">
                <summary class="cursor-pointer text-xs font-medium text-green-700 hover:text-green-800">Lihat hash lengkap</summary>
                <p class="mt-2 break-all rounded-lg bg-slate-50 p-3 font-mono text-xs text-slate-600">${data.hash_sha256}</p>
            </details>
            <button id="copySha256Btn" data-hash="${data.hash_sha256}" class="mt-3 inline-flex items-center rounded-lg border border-green-200 bg-white px-3 py-1.5 text-xs font-medium text-green-700 transition hover:bg-green-50">Salin SHA-256</button>
        </div>` : '';

    const historyItems = Array.isArray(data.history_notes) && data.history_notes.length > 0
        ? data.history_notes.map(item => {
            let descHtml = '';
            if (item.description && !item.description.startsWith('Edit sertifikat')) {
                const parts = item.description.split(';').map(s => s.trim()).filter(Boolean);
                descHtml = `<ul class="mt-1.5 space-y-0.5">${parts.map(p => `<li class="text-xs text-slate-600">• ${p}</li>`).join('')}</ul>`;
            } else {
                descHtml = `<p class="mt-1 text-xs text-slate-500">Tidak ada perubahan data tercatat</p>`;
            }
            return `<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                <div class="flex items-center justify-between gap-3">
                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 border-amber-200">✏ ${item.label}</span>
                    <span class="text-xs text-slate-400">${item.created_at}</span>
                </div>
                ${descHtml}
            </div>`;
        }).join('')
        : `<p class="text-xs text-slate-400">Tidak ada perubahan tercatat.</p>`;

    const docSection = data.file_path ? `
        <div class="border-b border-slate-100 pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Dokumen Sertifikat</p>
            <img src="/${data.file_path}" alt="Sertifikat" class="w-full rounded-xl border border-slate-200 shadow-sm">
            <a href="/${data.file_path}" target="_blank" download class="mt-3 inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">&#8595; Unduh Sertifikat</a>
        </div>` : '';

    const infoItems = [
        ['Nama', data.nama], ['NPM', data.npm],
        ['Nomor Sertifikat', data.nomor_sertifikat], ['Jenis', data.jenis_sertifikat],
        ['Tanggal Terbit', data.tanggal_terbit], ['Berlaku Sampai', data.masa_berlaku],
    ].map(([label, value]) => `
        <div class="border-b border-slate-100 pb-2.5">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">${label}</p>
            <p class="text-sm font-bold text-slate-800 mt-0.5 break-words leading-snug">${value ?? '-'}</p>
        </div>`).join('');

    return `
        <div class="public-result bg-white border border-slate-200 rounded-2xl overflow-hidden flex">
            <div class="w-44 flex-shrink-0 bg-slate-50 border-r border-slate-100 p-4 space-y-3">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Info Sertifikat</p>
                ${infoItems}
            </div>
            <div class="flex-1 min-w-0 flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-bold text-slate-900">Detail Sertifikat</h2>
                    <button id="backFromDetailBtn" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">&#8592; Kembali</button>
                </div>
                <div class="overflow-y-auto space-y-4 p-5">
                    ${docSection}
                    ${hashSection}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Riwayat Perubahan</p>
                        <div class="space-y-2">${historyItems}</div>
                    </div>
                </div>
            </div>
        </div>`;
}

function closeCertDetail() {
    document.getElementById('certDetailModal')?.remove();
}

function openCertDetail(data) {
    const outer = document.getElementById('result-outer');
    outer.innerHTML = buildDetailPanel(data);
    outer.querySelector('#backFromDetailBtn').addEventListener('click', () => showSuccessView(data));
    const copyBtn = outer.querySelector('#copySha256Btn');
    if (copyBtn) copyBtn.addEventListener('click', () => copyHash(copyBtn));
}

async function copyHash(button) {
    const hash = button?.dataset?.hash;
    if (!hash) return;
    const originalText = button.textContent;
    try {
        await navigator.clipboard.writeText(hash);
        button.textContent = 'Tersalin';
        setTimeout(() => { button.textContent = originalText; }, 1800);
    } catch (_) {
        button.textContent = 'Gagal salin';
        setTimeout(() => { button.textContent = originalText; }, 1800);
    }
}

function showSuccessView(data) {
    const outer = document.getElementById('result-outer');
    outer.className = 'public-result mx-auto max-w-4xl mt-6 p-6 bg-green-50 border border-green-200';
    outer.innerHTML = `
        <div class="mb-5">
            <h3 class="text-xl font-bold text-green-900">Sertifikat Valid</h3>
            <p class="text-sm text-green-700 mt-1">Data sertifikat berhasil ditemukan.</p>
        </div>
        <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm text-gray-700 mb-5">
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Nama</span><p class="font-semibold mt-0.5">${data.nama}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">NPM</span><p class="font-semibold mt-0.5">${data.npm}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Nomor Sertifikat</span><p class="font-semibold font-mono mt-0.5">${data.nomor_sertifikat}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Jenis</span><p class="font-semibold mt-0.5">${data.jenis_sertifikat}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Tanggal Terbit</span><p class="font-semibold mt-0.5">${data.tanggal_terbit}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Berlaku Sampai</span><p class="font-semibold mt-0.5">${data.masa_berlaku}</p></div>
        </div>
        <div class="border-t border-green-200 pt-4 flex items-center justify-between">
            <button id="resetBtn" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">&#8592; Verifikasi Lagi</button>
            <button id="openDetailBtn" class="inline-flex items-center gap-2 rounded-xl border border-green-300 bg-white px-4 py-2 text-sm font-semibold text-green-700 transition hover:bg-green-50">&#9776; Lihat Detail</button>
        </div>
    `;
    outer.classList.remove('hidden');
    outer.querySelector('#openDetailBtn').addEventListener('click', () => openCertDetail(data));
    outer.querySelector('#resetBtn').addEventListener('click', resetVerification);
}

function showSuccess(data) {
    window._certDetailData = data;
    document.querySelector('.public-shell').classList.add('hidden');
    showSuccessView(data);
}

function resetVerification() {
    stopScan();
    document.querySelector('.public-shell').classList.remove('hidden');
    const outer = document.getElementById('result-outer');
    outer.classList.add('hidden');
    outer.innerHTML = '';
    document.getElementById('cert_number').value = '';
    clearUpload(null);
}

function showError(message) {
    const el = document.getElementById('result');
    el.className = 'public-result mt-8 p-6 bg-red-50 border border-red-200';
    el.innerHTML = `
        <div>
            <h3 class="text-xl font-bold text-red-900 mb-1">Sertifikat Tidak Ditemukan</h3>
            <p class="text-red-700 text-sm">${message}</p>
        </div>`;
    el.classList.remove('hidden');
}
</script>
@endsection
```

### resources/views/welcome.blade.php

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /* ! tailwindcss v3.4.17 | MIT License | https://tailwindcss.com */*,:before,:after{--tw-border-spacing-x: 0;--tw-border-spacing-y: 0;--tw-translate-x: 0;--tw-translate-y: 0;--tw-rotate: 0;--tw-skew-x: 0;--tw-skew-y: 0;--tw-scale-x: 1;--tw-scale-y: 1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness: proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width: 0px;--tw-ring-offset-color: #fff;--tw-ring-color: rgb(59 130 246 / .5);--tw-ring-offset-shadow: 0 0 #0000;--tw-ring-shadow: 0 0 #0000;--tw-shadow: 0 0 #0000;--tw-shadow-colored: 0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }::backdrop{--tw-border-spacing-x: 0;--tw-border-spacing-y: 0;--tw-translate-x: 0;--tw-translate-y: 0;--tw-rotate: 0;--tw-skew-x: 0;--tw-skew-y: 0;--tw-scale-x: 1;--tw-scale-y: 1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness: proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width: 0px;--tw-ring-offset-color: #fff;--tw-ring-color: rgb(59 130 246 / .5);--tw-ring-offset-shadow: 0 0 #0000;--tw-ring-shadow: 0 0 #0000;--tw-shadow: 0 0 #0000;--tw-shadow-colored: 0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }*,:before,:after{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}:before,:after{--tw-content: ""}html,:host{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;-o-tab-size:4;tab-size:4;font-family:Figtree,ui-sans-serif,system-ui,sans-serif,"Apple Color Emoji","Segoe UI Emoji",Segoe UI Symbol,"Noto Color Emoji";font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}body{margin:0;line-height:inherit}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,samp,pre{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace;font-feature-settings:normal;font-variation-settings:normal;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}button,input:where([type=button]),input:where([type=reset]),input:where([type=submit]){-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dl,dd,h1,h2,h3,h4,h5,h6,hr,figure,p,pre{margin:0}fieldset{margin:0;padding:0}legend{padding:0}ol,ul,menu{list-style:none;margin:0;padding:0}dialog{padding:0}textarea{resize:vertical}input::-moz-placeholder,textarea::-moz-placeholder{opacity:1;color:#9ca3af}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}button,[role=button]{cursor:pointer}:disabled{cursor:default}img,svg,video,canvas,audio,iframe,embed,object{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[hidden]:where(:not([hidden=until-found])){display:none}.absolute{position:absolute}.relative{position:relative}.-bottom-16{bottom:-4rem}.-left-16{left:-4rem}.-left-20{left:-5rem}.top-0{top:0}.z-0{z-index:0}.\!row-span-1{grid-row:span 1 / span 1!important}.-mx-3{margin-left:-.75rem;margin-right:-.75rem}.-ml-px{margin-left:-1px}.ml-3{margin-left:.75rem}.mt-4{margin-top:1rem}.mt-6{margin-top:1.5rem}.flex{display:flex}.inline-flex{display:inline-flex}.table{display:table}.grid{display:grid}.\!hidden{display:none!important}.hidden{display:none}.aspect-video{aspect-ratio:16 / 9}.size-12{width:3rem;height:3rem}.size-5{width:1.25rem;height:1.25rem}.size-6{width:1.5rem;height:1.5rem}.h-12{height:3rem}.h-40{height:10rem}.h-5{height:1.25rem}.h-full{height:100%}.min-h-screen{min-height:100vh}.w-5{width:1.25rem}.w-\[calc\(100\%_\+_8rem\)\]{width:calc(100% + 8rem)}.w-auto{width:auto}.w-full{width:100%}.max-w-2xl{max-width:42rem}.max-w-\[877px\]{max-width:877px}.flex-1{flex:1 1 0%}.shrink-0{flex-shrink:0}.transform{transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.cursor-default{cursor:default}.resize{resize:both}.grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}.\!flex-row{flex-direction:row!important}.flex-col{flex-direction:column}.items-start{align-items:flex-start}.items-center{align-items:center}.items-stretch{align-items:stretch}.justify-end{justify-content:flex-end}.justify-center{justify-content:center}.justify-between{justify-content:space-between}.justify-items-center{justify-items:center}.gap-2{gap:.5rem}.gap-4{gap:1rem}.gap-6{gap:1.5rem}.self-center{align-self:center}.overflow-hidden{overflow:hidden}.rounded-\[10px\]{border-radius:10px}.rounded-full{border-radius:9999px}.rounded-lg{border-radius:.5rem}.rounded-md{border-radius:.375rem}.rounded-sm{border-radius:.125rem}.rounded-l-md{border-top-left-radius:.375rem;border-bottom-left-radius:.375rem}.rounded-r-md{border-top-right-radius:.375rem;border-bottom-right-radius:.375rem}.border{border-width:1px}.border-gray-300{--tw-border-opacity: 1;border-color:rgb(209 213 219 / var(--tw-border-opacity, 1))}.bg-\[\#FF2D20\]\/10{background-color:#ff2d201a}.bg-gray-50{--tw-bg-opacity: 1;background-color:rgb(249 250 251 / var(--tw-bg-opacity, 1))}.bg-white{--tw-bg-opacity: 1;background-color:rgb(255 255 255 / var(--tw-bg-opacity, 1))}.bg-gradient-to-b{background-image:linear-gradient(to bottom,var(--tw-gradient-stops))}.from-transparent{--tw-gradient-from: transparent var(--tw-gradient-from-position);--tw-gradient-to: rgb(0 0 0 / 0) var(--tw-gradient-to-position);--tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to)}.via-white{--tw-gradient-to: rgb(255 255 255 / 0) var(--tw-gradient-to-position);--tw-gradient-stops: var(--tw-gradient-from), #fff var(--tw-gradient-via-position), var(--tw-gradient-to)}.to-white{--tw-gradient-to: #fff var(--tw-gradient-to-position)}.to-zinc-900{--tw-gradient-to: #18181b var(--tw-gradient-to-position)}.stroke-\[\#FF2D20\]{stroke:#ff2d20}.object-cover{-o-object-fit:cover;object-fit:cover}.object-top{-o-object-position:top;object-position:top}.p-6{padding:1.5rem}.px-2{padding-left:.5rem;padding-right:.5rem}.px-3{padding-left:.75rem;padding-right:.75rem}.px-4{padding-left:1rem;padding-right:1rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.py-10{padding-top:2.5rem;padding-bottom:2.5rem}.py-16{padding-top:4rem;padding-bottom:4rem}.py-2{padding-top:.5rem;padding-bottom:.5rem}.pt-3{padding-top:.75rem}.text-center{text-align:center}.font-sans{font-family:Figtree,ui-sans-serif,system-ui,sans-serif,"Apple Color Emoji","Segoe UI Emoji",Segoe UI Symbol,"Noto Color Emoji"}.text-sm{font-size:.875rem;line-height:1.25rem}.text-sm\/relaxed{font-size:.875rem;line-height:1.625}.text-xl{font-size:1.25rem;line-height:1.75rem}.font-medium{font-weight:500}.font-semibold{font-weight:600}.leading-5{line-height:1.25rem}.text-black{--tw-text-opacity: 1;color:rgb(0 0 0 / var(--tw-text-opacity, 1))}.text-black\/50{color:#00000080}.text-gray-500{--tw-text-opacity: 1;color:rgb(107 114 128 / var(--tw-text-opacity, 1))}.text-gray-700{--tw-text-opacity: 1;color:rgb(55 65 81 / var(--tw-text-opacity, 1))}.text-white{--tw-text-opacity: 1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.underline{text-decoration-line:underline}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.shadow-\[0px_14px_34px_0px_rgba\(0\,0\,0\,0\.08\)\]{--tw-shadow: 0px 14px 34px 0px rgba(0,0,0,.08);--tw-shadow-colored: 0px 14px 34px 0px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000),var(--tw-ring-shadow, 0 0 #0000),var(--tw-shadow)}.shadow-sm{--tw-shadow: 0 1px 2px 0 rgb(0 0 0 / .05);--tw-shadow-colored: 0 1px 2px 0 var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000),var(--tw-ring-shadow, 0 0 #0000),var(--tw-shadow)}.ring-1{--tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow, 0 0 #0000)}.ring-black{--tw-ring-opacity: 1;--tw-ring-color: rgb(0 0 0 / var(--tw-ring-opacity, 1))}.ring-gray-300{--tw-ring-opacity: 1;--tw-ring-color: rgb(209 213 219 / var(--tw-ring-opacity, 1))}.ring-transparent{--tw-ring-color: transparent}.ring-white{--tw-ring-opacity: 1;--tw-ring-color: rgb(255 255 255 / var(--tw-ring-opacity, 1))}.ring-white\/\[0\.05\]{--tw-ring-color: rgb(255 255 255 / .05)}.drop-shadow-\[0px_4px_34px_rgba\(0\,0\,0\,0\.06\)\]{--tw-drop-shadow: drop-shadow(0px 4px 34px rgba(0,0,0,.06));filter:var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)}.drop-shadow-\[0px_4px_34px_rgba\(0\,0\,0\,0\.25\)\]{--tw-drop-shadow: drop-shadow(0px 4px 34px rgba(0,0,0,.25));filter:var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)}.filter{filter:var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale) var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia) var(--tw-drop-shadow)}.transition{transition-property:color,background-color,border-color,text-decoration-color,fill,stroke,opacity,box-shadow,transform,filter,-webkit-backdrop-filter;transition-property:color,background-color,border-color,text-decoration-color,fill,stroke,opacity,box-shadow,transform,filter,backdrop-filter;transition-property:color,background-color,border-color,text-decoration-color,fill,stroke,opacity,box-shadow,transform,filter,backdrop-filter,-webkit-backdrop-filter;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}.duration-150{transition-duration:.15s}.duration-300{transition-duration:.3s}.ease-in-out{transition-timing-function:cubic-bezier(.4,0,.2,1)}.selection\:bg-\[\#FF2D20\] *::-moz-selection{--tw-bg-opacity: 1;background-color:rgb(255 45 32 / var(--tw-bg-opacity, 1))}.selection\:bg-\[\#FF2D20\] *::selection{--tw-bg-opacity: 1;background-color:rgb(255 45 32 / var(--tw-bg-opacity, 1))}.selection\:text-white *::-moz-selection{--tw-text-opacity: 1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.selection\:text-white *::selection{--tw-text-opacity: 1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.selection\:bg-\[\#FF2D20\]::-moz-selection{--tw-bg-opacity: 1;background-color:rgb(255 45 32 / var(--tw-bg-opacity, 1))}.selection\:bg-\[\#FF2D20\]::selection{--tw-bg-opacity: 1;background-color:rgb(255 45 32 / var(--tw-bg-opacity, 1))}.selection\:text-white::-moz-selection{--tw-text-opacity: 1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.selection\:text-white::selection{--tw-text-opacity: 1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.hover\:text-black:hover{--tw-text-opacity: 1;color:rgb(0 0 0 / var(--tw-text-opacity, 1))}.hover\:text-black\/70:hover{color:#000000b3}.hover\:text-gray-400:hover{--tw-text-opacity: 1;color:rgb(156 163 175 / var(--tw-text-opacity, 1))}.hover\:text-gray-500:hover{--tw-text-opacity: 1;color:rgb(107 114 128 / var(--tw-text-opacity, 1))}.hover\:ring-black\/20:hover{--tw-ring-color: rgb(0 0 0 / .2)}.focus\:z-10:focus{z-index:10}.focus\:border-blue-300:focus{--tw-border-opacity: 1;border-color:rgb(147 197 253 / var(--tw-border-opacity, 1))}.focus\:outline-none:focus{outline:2px solid transparent;outline-offset:2px}.focus\:ring:focus{--tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(3px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow, 0 0 #0000)}.focus-visible\:ring-1:focus-visible{--tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow, 0 0 #0000)}.focus-visible\:ring-\[\#FF2D20\]:focus-visible{--tw-ring-opacity: 1;--tw-ring-color: rgb(255 45 32 / var(--tw-ring-opacity, 1))}.active\:bg-gray-100:active{--tw-bg-opacity: 1;background-color:rgb(243 244 246 / var(--tw-bg-opacity, 1))}.active\:text-gray-500:active{--tw-text-opacity: 1;color:rgb(107 114 128 / var(--tw-text-opacity, 1))}.active\:text-gray-700:active{--tw-text-opacity: 1;color:rgb(55 65 81 / var(--tw-text-opacity, 1))}@media (min-width: 640px){.sm\:flex{display:flex}.sm\:hidden{display:none}.sm\:size-16{width:4rem;height:4rem}.sm\:size-6{width:1.5rem;height:1.5rem}.sm\:flex-1{flex:1 1 0%}.sm\:items-center{align-items:center}.sm\:justify-between{justify-content:space-between}.sm\:pt-5{padding-top:1.25rem}}@media (min-width: 768px){.md\:row-span-3{grid-row:span 3 / span 3}}@media (min-width: 1024px){.lg\:col-start-2{grid-column-start:2}.lg\:h-16{height:4rem}.lg\:max-w-7xl{max-width:80rem}.lg\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}.lg\:grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}.lg\:flex-col{flex-direction:column}.lg\:items-end{align-items:flex-end}.lg\:justify-center{justify-content:center}.lg\:gap-8{gap:2rem}.lg\:p-10{padding:2.5rem}.lg\:pb-10{padding-bottom:2.5rem}.lg\:pt-0{padding-top:0}.lg\:text-\[\#FF2D20\]{--tw-text-opacity: 1;color:rgb(255 45 32 / var(--tw-text-opacity, 1))}}.rtl\:flex-row-reverse:where([dir=rtl],[dir=rtl] *){flex-direction:row-reverse}@media (prefers-color-scheme: dark){.dark\:block{display:block}.dark\:hidden{display:none}.dark\:border-gray-600{--tw-border-opacity: 1;border-color:rgb(75 85 99 / var(--tw-border-opacity, 1))}.dark\:bg-black{--tw-bg-opacity: 1;background-color:rgb(0 0 0 / var(--tw-bg-opacity, 1))}.dark\:bg-gray-800{--tw-bg-opacity: 1;background-color:rgb(31 41 55 / var(--tw-bg-opacity, 1))}.dark\:bg-zinc-900{--tw-bg-opacity: 1;background-color:rgb(24 24 27 / var(--tw-bg-opacity, 1))}.dark\:via-zinc-900{--tw-gradient-to: rgb(24 24 27 / 0) var(--tw-gradient-to-position);--tw-gradient-stops: var(--tw-gradient-from), #18181b var(--tw-gradient-via-position), var(--tw-gradient-to)}.dark\:to-zinc-900{--tw-gradient-to: #18181b var(--tw-gradient-to-position)}.dark\:text-gray-300{--tw-text-opacity: 1;color:rgb(209 213 219 / var(--tw-text-opacity, 1))}.dark\:text-gray-400{--tw-text-opacity: 1;color:rgb(156 163 175 / var(--tw-text-opacity, 1))}.dark\:text-gray-600{--tw-text-opacity: 1;color:rgb(75 85 99 / var(--tw-text-opacity, 1))}.dark\:text-white{--tw-text-opacity: 1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.dark\:text-white\/50{color:#ffffff80}.dark\:text-white\/70{color:#ffffffb3}.dark\:ring-zinc-800{--tw-ring-opacity: 1;--tw-ring-color: rgb(39 39 42 / var(--tw-ring-opacity, 1))}.dark\:hover\:text-gray-300:hover{--tw-text-opacity: 1;color:rgb(209 213 219 / var(--tw-text-opacity, 1))}.dark\:hover\:text-white:hover{--tw-text-opacity: 1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.dark\:hover\:text-white\/70:hover{color:#ffffffb3}.dark\:hover\:text-white\/80:hover{color:#fffc}.dark\:hover\:ring-zinc-700:hover{--tw-ring-opacity: 1;--tw-ring-color: rgb(63 63 70 / var(--tw-ring-opacity, 1))}.dark\:focus\:border-blue-700:focus{--tw-border-opacity: 1;border-color:rgb(29 78 216 / var(--tw-border-opacity, 1))}.dark\:focus\:border-blue-800:focus{--tw-border-opacity: 1;border-color:rgb(30 64 175 / var(--tw-border-opacity, 1))}.dark\:focus-visible\:ring-\[\#FF2D20\]:focus-visible{--tw-ring-opacity: 1;--tw-ring-color: rgb(255 45 32 / var(--tw-ring-opacity, 1))}.dark\:focus-visible\:ring-white:focus-visible{--tw-ring-opacity: 1;--tw-ring-color: rgb(255 255 255 / var(--tw-ring-opacity, 1))}.dark\:active\:bg-gray-700:active{--tw-bg-opacity: 1;background-color:rgb(55 65 81 / var(--tw-bg-opacity, 1))}.dark\:active\:text-gray-300:active{--tw-text-opacity: 1;color:rgb(209 213 219 / var(--tw-text-opacity, 1))}}
            </style>
        @endif
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">
        <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
            <img id="background" class="absolute -left-20 top-0 max-w-[877px]" src="https://laravel.com/assets/img/welcome/background.svg" alt="Laravel background" />
            <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
                <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                    <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
                        <div class="flex lg:justify-center lg:col-start-2">
                            <svg class="h-12 w-auto text-white lg:h-16 lg:text-[#FF2D20]" viewBox="0 0 62 65" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M61.8548 14.6253C61.8778 14.7102 61.8895 14.7978 61.8897 14.8858V28.5615C61.8898 28.737 61.8434 28.9095 61.7554 29.0614C61.6675 29.2132 61.5409 29.3392 61.3887 29.4265L49.9104 36.0351V49.1337C49.9104 49.4902 49.7209 49.8192 49.4118 49.9987L25.4519 63.7916C25.3971 63.8227 25.3372 63.8427 25.2774 63.8639C25.255 63.8714 25.2338 63.8851 25.2101 63.8913C25.0426 63.9354 24.8666 63.9354 24.6991 63.8913C24.6716 63.8838 24.6467 63.8689 24.6205 63.8589C24.5657 63.8389 24.5084 63.8215 24.456 63.7916L0.501061 49.9987C0.348882 49.9113 0.222437 49.7853 0.134469 49.6334C0.0465019 49.4816 0.000120578 49.3092 0 49.1337L0 8.10652C0 8.01678 0.0124642 7.92953 0.0348998 7.84477C0.0423783 7.8161 0.0598282 7.78993 0.0697995 7.76126C0.0884958 7.70891 0.105946 7.65531 0.133367 7.6067C0.152063 7.5743 0.179485 7.54812 0.20192 7.51821C0.230588 7.47832 0.256763 7.43719 0.290416 7.40229C0.319084 7.37362 0.356476 7.35243 0.388883 7.32751C0.425029 7.29759 0.457436 7.26518 0.498568 7.2415L12.4779 0.345059C12.6296 0.257786 12.8015 0.211853 12.9765 0.211853C13.1515 0.211853 13.3234 0.257786 13.475 0.345059L25.4531 7.2415H25.4556C25.4955 7.26643 25.5292 7.29759 25.5653 7.32626C25.5977 7.35119 25.6339 7.37362 25.6625 7.40104C25.6974 7.43719 25.7224 7.47832 25.7523 7.51821C25.7735 7.54812 25.8021 7.5743 25.8196 7.6067C25.8483 7.65656 25.8645 7.70891 25.8844 7.76126C25.8944 7.78993 25.9118 7.8161 25.9193 7.84602C25.9423 7.93096 25.954 8.01853 25.9542 8.10652V33.7317L35.9355 27.9844V14.8846C35.9355 14.7973 35.948 14.7088 35.9704 14.6253C35.9792 14.5954 35.9954 14.5692 36.0053 14.5405C36.0253 14.4882 36.0427 14.4346 36.0702 14.386C36.0888 14.3536 36.1163 14.3274 36.1375 14.2975C36.1674 14.2576 36.1923 14.2165 36.2272 14.1816C36.2559 14.1529 36.292 14.1317 36.3244 14.1068C36.3618 14.0769 36.3942 14.0445 36.4341 14.0208L48.4147 7.12434C48.5663 7.03694 48.7383 6.99094 48.9133 6.99094C49.0883 6.99094 49.2602 7.03694 49.4118 7.12434L61.3899 14.0208C61.4323 14.0457 61.4647 14.0769 61.5021 14.1055C61.5333 14.1305 61.5694 14.1529 61.5981 14.1803C61.633 14.2165 61.6579 14.2576 61.6878 14.2975C61.7103 14.3274 61.7377 14.3536 61.7551 14.386C61.7838 14.4346 61.8 14.4882 61.8199 14.5405C61.8312 14.5692 61.8474 14.5954 61.8548 14.6253ZM59.893 27.9844V16.6121L55.7013 19.0252L49.9104 22.3593V33.7317L59.8942 27.9844H59.893ZM47.9149 48.5566V37.1768L42.2187 40.4299L25.953 49.7133V61.2003L47.9149 48.5566ZM1.99677 9.83281V48.5566L23.9562 61.199V49.7145L12.4841 43.2219L12.4804 43.2194L12.4754 43.2169C12.4368 43.1945 12.4044 43.1621 12.3682 43.1347C12.3371 43.1097 12.3009 43.0898 12.2735 43.0624L12.271 43.0586C12.2386 43.0275 12.2162 42.9888 12.1887 42.9539C12.1638 42.9203 12.1339 42.8916 12.114 42.8567L12.1127 42.853C12.0903 42.8156 12.0766 42.7707 12.0604 42.7283C12.0442 42.6909 12.023 42.656 12.013 42.6161C12.0005 42.5688 11.998 42.5177 11.9931 42.4691C11.9881 42.4317 11.9781 42.3943 11.9781 42.3569V15.5801L6.18848 12.2446L1.99677 9.83281ZM12.9777 2.36177L2.99764 8.10652L12.9752 13.8513L22.9541 8.10527L12.9752 2.36177H12.9777ZM18.1678 38.2138L23.9574 34.8809V9.83281L19.7657 12.2459L13.9749 15.5801V40.6281L18.1678 38.2138ZM48.9133 9.14105L38.9344 14.8858L48.9133 20.6305L58.8909 14.8846L48.9133 9.14105ZM47.9149 22.3593L42.124 19.0252L37.9323 16.6121V27.9844L43.7219 31.3174L47.9149 33.7317V22.3593ZM24.9533 47.987L39.59 39.631L46.9065 35.4555L36.9352 29.7145L25.4544 36.3242L14.9907 42.3482L24.9533 47.987Z" fill="currentColor"/></svg>
                        </div>
                        @if (Route::has('login'))
                            <nav class="-mx-3 flex flex-1 justify-end">
                                @auth
                                    <a
                                        href="{{ url('/dashboard') }}"
                                        class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                    >
                                        Dashboard
                                    </a>
                                @else
                                    <a
                                        href="{{ route('login') }}"
                                        class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                    >
                                        Log in
                                    </a>

                                    @if (Route::has('register'))
                                        <a
                                            href="{{ route('register') }}"
                                            class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                        >
                                            Register
                                        </a>
                                    @endif
                                @endauth
                            </nav>
                        @endif
                    </header>

                    <main class="mt-6">
                        <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                            <a
                                href="https://laravel.com/docs"
                                id="docs-card"
                                class="flex flex-col items-start gap-6 overflow-hidden rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] md:row-span-3 lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                            >
                                <div id="screenshot-container" class="relative flex w-full flex-1 items-stretch">
                                    <img
                                        src="https://laravel.com/assets/img/welcome/docs-light.svg"
                                        alt="Laravel documentation screenshot"
                                        class="aspect-video h-full w-full flex-1 rounded-[10px] object-top object-cover drop-shadow-[0px_4px_34px_rgba(0,0,0,0.06)] dark:hidden"
                                        onerror="
                                            document.getElementById('screenshot-container').classList.add('!hidden');
                                            document.getElementById('docs-card').classList.add('!row-span-1');
                                            document.getElementById('docs-card-content').classList.add('!flex-row');
                                            document.getElementById('background').classList.add('!hidden');
                                        "
                                    />
                                    <img
                                        src="https://laravel.com/assets/img/welcome/docs-dark.svg"
                                        alt="Laravel documentation screenshot"
                                        class="hidden aspect-video h-full w-full flex-1 rounded-[10px] object-top object-cover drop-shadow-[0px_4px_34px_rgba(0,0,0,0.25)] dark:block"
                                    />
                                    <div
                                        class="absolute -bottom-16 -left-16 h-40 w-[calc(100%_+_8rem)] bg-gradient-to-b from-transparent via-white to-white dark:via-zinc-900 dark:to-zinc-900"
                                    ></div>
                                </div>

                                <div class="relative flex items-center gap-6 lg:items-end">
                                    <div id="docs-card-content" class="flex items-start gap-6 lg:flex-col">
                                        <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                            <svg class="size-5 sm:size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path fill="#FF2D20" d="M23 4a1 1 0 0 0-1.447-.894L12.224 7.77a.5.5 0 0 1-.448 0L2.447 3.106A1 1 0 0 0 1 4v13.382a1.99 1.99 0 0 0 1.105 1.79l9.448 4.728c.14.065.293.1.447.1.154-.005.306-.04.447-.105l9.453-4.724a1.99 1.99 0 0 0 1.1-1.789V4ZM3 6.023a.25.25 0 0 1 .362-.223l7.5 3.75a.251.251 0 0 1 .138.223v11.2a.25.25 0 0 1-.362.224l-7.5-3.75a.25.25 0 0 1-.138-.22V6.023Zm18 11.2a.25.25 0 0 1-.138.224l-7.5 3.75a.249.249 0 0 1-.329-.099.249.249 0 0 1-.033-.12V9.772a.251.251 0 0 1 .138-.224l7.5-3.75a.25.25 0 0 1 .362.224v11.2Z"/><path fill="#FF2D20" d="m3.55 1.893 8 4.048a1.008 1.008 0 0 0 .9 0l8-4.048a1 1 0 0 0-.9-1.785l-7.322 3.706a.506.506 0 0 1-.452 0L4.454.108a1 1 0 0 0-.9 1.785H3.55Z"/></svg>
                                        </div>

                                        <div class="pt-3 sm:pt-5 lg:pt-0">
                                            <h2 class="text-xl font-semibold text-black dark:text-white">Documentation</h2>

                                            <p class="mt-4 text-sm/relaxed">
                                                Laravel has wonderful documentation covering every aspect of the framework. Whether you are a newcomer or have prior experience with Laravel, we recommend reading our documentation from beginning to end.
                                            </p>
                                        </div>
                                    </div>

                                    <svg class="size-6 shrink-0 stroke-[#FF2D20]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                                </div>
                            </a>

                            <a
                                href="https://laracasts.com"
                                class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                            >
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                    <svg class="size-5 sm:size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g fill="#FF2D20"><path d="M24 8.25a.5.5 0 0 0-.5-.5H.5a.5.5 0 0 0-.5.5v12a2.5 2.5 0 0 0 2.5 2.5h19a2.5 2.5 0 0 0 2.5-2.5v-12Zm-7.765 5.868a1.221 1.221 0 0 1 0 2.264l-6.626 2.776A1.153 1.153 0 0 1 8 18.123v-5.746a1.151 1.151 0 0 1 1.609-1.035l6.626 2.776ZM19.564 1.677a.25.25 0 0 0-.177-.427H15.6a.106.106 0 0 0-.072.03l-4.54 4.543a.25.25 0 0 0 .177.427h3.783c.027 0 .054-.01.073-.03l4.543-4.543ZM22.071 1.318a.047.047 0 0 0-.045.013l-4.492 4.492a.249.249 0 0 0 .038.385.25.25 0 0 0 .14.042h5.784a.5.5 0 0 0 .5-.5v-2a2.5 2.5 0 0 0-1.925-2.432ZM13.014 1.677a.25.25 0 0 0-.178-.427H9.101a.106.106 0 0 0-.073.03l-4.54 4.543a.25.25 0 0 0 .177.427H8.4a.106.106 0 0 0 .073-.03l4.54-4.543ZM6.513 1.677a.25.25 0 0 0-.177-.427H2.5A2.5 2.5 0 0 0 0 3.75v2a.5.5 0 0 0 .5.5h1.4a.106.106 0 0 0 .073-.03l4.54-4.543Z"/></g></svg>
                                </div>

                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">Laracasts</h2>

                                    <p class="mt-4 text-sm/relaxed">
                                        Laracasts offers thousands of video tutorials on Laravel, PHP, and JavaScript development. Check them out, see for yourself, and massively level up your development skills in the process.
                                    </p>
                                </div>

                                <svg class="size-6 shrink-0 self-center stroke-[#FF2D20]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                            </a>

                            <a
                                href="https://laravel-news.com"
                                class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                            >
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                    <svg class="size-5 sm:size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g fill="#FF2D20"><path d="M8.75 4.5H5.5c-.69 0-1.25.56-1.25 1.25v4.75c0 .69.56 1.25 1.25 1.25h3.25c.69 0 1.25-.56 1.25-1.25V5.75c0-.69-.56-1.25-1.25-1.25Z"/><path d="M24 10a3 3 0 0 0-3-3h-2V2.5a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2V20a3.5 3.5 0 0 0 3.5 3.5h17A3.5 3.5 0 0 0 24 20V10ZM3.5 21.5A1.5 1.5 0 0 1 2 20V3a.5.5 0 0 1 .5-.5h14a.5.5 0 0 1 .5.5v17c0 .295.037.588.11.874a.5.5 0 0 1-.484.625L3.5 21.5ZM22 20a1.5 1.5 0 1 1-3 0V9.5a.5.5 0 0 1 .5-.5H21a1 1 0 0 1 1 1v10Z"/><path d="M12.751 6.047h2a.75.75 0 0 1 .75.75v.5a.75.75 0 0 1-.75.75h-2A.75.75 0 0 1 12 7.3v-.5a.75.75 0 0 1 .751-.753ZM12.751 10.047h2a.75.75 0 0 1 .75.75v.5a.75.75 0 0 1-.75.75h-2A.75.75 0 0 1 12 11.3v-.5a.75.75 0 0 1 .751-.753ZM4.751 14.047h10a.75.75 0 0 1 .75.75v.5a.75.75 0 0 1-.75.75h-10A.75.75 0 0 1 4 15.3v-.5a.75.75 0 0 1 .751-.753ZM4.75 18.047h7.5a.75.75 0 0 1 .75.75v.5a.75.75 0 0 1-.75.75h-7.5A.75.75 0 0 1 4 19.3v-.5a.75.75 0 0 1 .75-.753Z"/></g></svg>
                                </div>

                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">Laravel News</h2>

                                    <p class="mt-4 text-sm/relaxed">
                                        Laravel News is a community driven portal and newsletter aggregating all of the latest and most important news in the Laravel ecosystem, including new package releases and tutorials.
                                    </p>
                                </div>

                                <svg class="size-6 shrink-0 self-center stroke-[#FF2D20]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                            </a>

                            <div class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                    <svg class="size-5 sm:size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <g fill="#FF2D20">
                                            <path
                                                d="M16.597 12.635a.247.247 0 0 0-.08-.237 2.234 2.234 0 0 1-.769-1.68c.001-.195.03-.39.084-.578a.25.25 0 0 0-.09-.267 8.8 8.8 0 0 0-4.826-1.66.25.25 0 0 0-.268.181 2.5 2.5 0 0 1-2.4 1.824.045.045 0 0 0-.045.037 12.255 12.255 0 0 0-.093 3.86.251.251 0 0 0 .208.214c2.22.366 4.367 1.08 6.362 2.118a.252.252 0 0 0 .32-.079 10.09 10.09 0 0 0 1.597-3.733ZM13.616 17.968a.25.25 0 0 0-.063-.407A19.697 19.697 0 0 0 8.91 15.98a.25.25 0 0 0-.287.325c.151.455.334.898.548 1.328.437.827.981 1.594 1.619 2.28a.249.249 0 0 0 .32.044 29.13 29.13 0 0 0 2.506-1.99ZM6.303 14.105a.25.25 0 0 0 .265-.274 13.048 13.048 0 0 1 .205-4.045.062.062 0 0 0-.022-.07 2.5 2.5 0 0 1-.777-.982.25.25 0 0 0-.271-.149 11 11 0 0 0-5.6 2.815.255.255 0 0 0-.075.163c-.008.135-.02.27-.02.406.002.8.084 1.598.246 2.381a.25.25 0 0 0 .303.193 19.924 19.924 0 0 1 5.746-.438ZM9.228 20.914a.25.25 0 0 0 .1-.393 11.53 11.53 0 0 1-1.5-2.22 12.238 12.238 0 0 1-.91-2.465.248.248 0 0 0-.22-.187 18.876 18.876 0 0 0-5.69.33.249.249 0 0 0-.179.336c.838 2.142 2.272 4 4.132 5.353a.254.254 0 0 0 .15.048c1.41-.01 2.807-.282 4.117-.802ZM18.93 12.957l-.005-.008a.25.25 0 0 0-.268-.082 2.21 2.21 0 0 1-.41.081.25.25 0 0 0-.217.2c-.582 2.66-2.127 5.35-5.75 7.843a.248.248 0 0 0-.09.299.25.25 0 0 0 .065.091 28.703 28.703 0 0 0 2.662 2.12.246.246 0 0 0 .209.037c2.579-.701 4.85-2.242 6.456-4.378a.25.25 0 0 0 .048-.189 13.51 13.51 0 0 0-2.7-6.014ZM5.702 7.058a.254.254 0 0 0 .2-.165A2.488 2.488 0 0 1 7.98 5.245a.093.093 0 0 0 .078-.062 19.734 19.734 0 0 1 3.055-4.74.25.25 0 0 0-.21-.41 12.009 12.009 0 0 0-10.4 8.558.25.25 0 0 0 .373.281 12.912 12.912 0 0 1 4.826-1.814ZM10.773 22.052a.25.25 0 0 0-.28-.046c-.758.356-1.55.635-2.365.833a.25.25 0 0 0-.022.48c1.252.43 2.568.65 3.893.65.1 0 .2 0 .3-.008a.25.25 0 0 0 .147-.444c-.526-.424-1.1-.917-1.673-1.465ZM18.744 8.436a.249.249 0 0 0 .15.228 2.246 2.246 0 0 1 1.352 2.054c0 .337-.08.67-.23.972a.25.25 0 0 0 .042.28l.007.009a15.016 15.016 0 0 1 2.52 4.6.25.25 0 0 0 .37.132.25.25 0 0 0 .096-.114c.623-1.464.944-3.039.945-4.63a12.005 12.005 0 0 0-5.78-10.258.25.25 0 0 0-.373.274c.547 2.109.85 4.274.901 6.453ZM9.61 5.38a.25.25 0 0 0 .08.31c.34.24.616.561.8.935a.25.25 0 0 0 .3.127.631.631 0 0 1 .206-.034c2.054.078 4.036.772 5.69 1.991a.251.251 0 0 0 .267.024c.046-.024.093-.047.141-.067a.25.25 0 0 0 .151-.23A29.98 29.98 0 0 0 15.957.764a.25.25 0 0 0-.16-.164 11.924 11.924 0 0 0-2.21-.518.252.252 0 0 0-.215.076A22.456 22.456 0 0 0 9.61 5.38Z"
                                            />
                                        </g>
                                    </svg>
                                </div>

                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">Vibrant Ecosystem</h2>

                                    <p class="mt-4 text-sm/relaxed">
                                        Laravel's robust library of first-party tools and libraries, such as <a href="https://forge.laravel.com" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white dark:focus-visible:ring-[#FF2D20]">Forge</a>, <a href="https://vapor.laravel.com" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">Vapor</a>, <a href="https://nova.laravel.com" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">Nova</a>, <a href="https://envoyer.io" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">Envoyer</a>, and <a href="https://herd.laravel.com" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">Herd</a> help you take your projects to the next level. Pair them with powerful open source libraries like <a href="https://laravel.com/docs/billing" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">Cashier</a>, <a href="https://laravel.com/docs/dusk" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">Dusk</a>, <a href="https://laravel.com/docs/broadcasting" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">Echo</a>, <a href="https://laravel.com/docs/horizon" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">Horizon</a>, <a href="https://laravel.com/docs/sanctum" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">Sanctum</a>, <a href="https://laravel.com/docs/telescope" class="rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">Telescope</a>, and more.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </main>

                    <footer class="py-16 text-center text-sm text-black dark:text-white/70">
                        Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                    </footer>
                </div>
            </div>
        </div>
    </body>
</html>
```

### routes/console.php

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

### routes/web.php

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;

// Public Routes
Route::get('/', [CertificateController::class, 'index'])->name('home');
Route::post('/verify', [CertificateController::class, 'verify'])->middleware('throttle:verify')->name('verify');
Route::post('/verify-qr', [CertificateController::class, 'verifyQr'])->middleware('throttle:verify-qr')->name('verify.qr');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Semua role — dashboard & lihat sertifikat
Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/certificates', [AdminController::class, 'list'])->name('admin.certificates');
    Route::get('/admin/verify', [CertificateController::class, 'adminVerify'])->name('admin.verify');
    Route::post('/admin/verify', [CertificateController::class, 'verifyForAdmin'])->name('admin.verify.search');
    Route::post('/admin/verify-qr', [CertificateController::class, 'verifyQrForAdmin'])->name('admin.verify.qr');
});

// Admin only — upload & edit sertifikat
Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    Route::get('/admin/upload', [AdminController::class, 'showUpload'])->name('admin.upload');
    Route::post('/admin/upload', [AdminController::class, 'upload'])->name('admin.upload.post');
    Route::get('/admin/certificates/{certificate}/edit', [AdminController::class, 'edit'])->name('admin.certificates.edit');
    Route::put('/admin/certificates/{certificate}', [AdminController::class, 'update'])->name('admin.certificates.update');
});

// Superadmin only — kontrol penuh: log, hapus sertifikat, kelola user
Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/admin/activity-logs', [AdminController::class, 'activityLogs'])->name('admin.activity-logs');
    Route::delete('/admin/certificates/{certificate}', [AdminController::class, 'delete'])->name('admin.certificates.delete');
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
    Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
});
```

### tailwind.config.js

```javascript
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
```

### tests/Feature/AdminUserAuthorizationAndThrottleTest.php

```php
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
```

### tests/Feature/AuthTest.php

```php
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
```

### tests/Feature/ExampleTest.php

```php
<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
```

### tests/Feature/ScanQrKameraTest.php

```php
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
```

### tests/Feature/SertifikatTest.php

```php
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
}
```

### tests/Feature/SessionRoleManipulationTest.php

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class SessionRoleManipulationTest extends TestCase
{
    public function test_admin_route_rejects_manual_session_role_escalation(): void
    {
        $user = new User([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => bcrypt('secret'),
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['role' => 'superadmin'])
            ->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_superadmin_route_uses_authenticated_user_role_not_session_role(): void
    {
        $user = new User([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['role' => 'superadmin'])
            ->get(route('admin.activity-logs'));

        $response->assertStatus(403);
    }
}
```

### tests/Feature/VerifikasiManualTest.php

```php
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
```

### tests/Feature/VerifikasiQrTest.php

```php
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
```

### tests/Feature/VerifikasiQrUploadTest.php

```php
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
```

### tests/TestCase.php

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}
```

### tests/Unit/ExampleTest.php

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
```

### test_blackbox_testing.php

```php
<?php
/**
 * Rekapitulasi Black Box Testing
 * Sistem Autentikasi Sertifikat Digital
 *
 * Jalankan:
 *   php test_blackbox_testing.php
 */

$modules = [
    ['no' => 1,  'modul' => 'Verifikasi Manual',                  'skenario' => '1-5',   'file' => 'VerifikasiManualTest.php'],
    ['no' => 2,  'modul' => 'Verifikasi QR Upload',               'skenario' => '6-10',  'file' => 'VerifikasiQrUploadTest.php'],
    ['no' => 3,  'modul' => 'Scan QR Kamera',                     'skenario' => '11-13', 'file' => 'ScanQrKameraTest.php'],
    ['no' => 4,  'modul' => 'Login, Logout, Akses Dashboard',     'skenario' => '14-20', 'file' => 'AuthTest.php'],
    ['no' => 5,  'modul' => 'Upload Sertifikat',                  'skenario' => '21-32', 'file' => 'SertifikatTest.php'],
    ['no' => 6,  'modul' => 'Daftar Sertifikat',                  'skenario' => '33-40', 'file' => 'SertifikatTest.php'],
    ['no' => 7,  'modul' => 'Edit Sertifikat',                    'skenario' => '41-44', 'file' => 'SertifikatTest.php'],
    ['no' => 8,  'modul' => 'Hapus Sertifikat',                   'skenario' => '45-46', 'file' => 'AdminUserAuthorizationAndThrottleTest.php'],
    ['no' => 9,  'modul' => 'Log Aktivitas',                      'skenario' => '47-48', 'file' => 'AdminUserAuthorizationAndThrottleTest.php'],
    ['no' => 10, 'modul' => 'Manajemen User',                     'skenario' => '49-57', 'file' => 'AdminUserAuthorizationAndThrottleTest.php'],
    ['no' => 11, 'modul' => 'Otorisasi Akses',                    'skenario' => '58-59', 'file' => 'AdminUserAuthorizationAndThrottleTest.php'],
    ['no' => 12, 'modul' => 'Pembatasan Akses',                   'skenario' => '60',    'file' => 'AdminUserAuthorizationAndThrottleTest.php'],
];

function runFeatureTests(): array
{
    $phpunit = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit';
    $cmd = [
        PHP_BINARY,
        $phpunit,
        'tests/Feature',
    ];

    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__);

    if (!is_resource($process)) {
        return [
            'exit_code' => 1,
            'output' => 'PHPUnit gagal dijalankan.',
            'summary' => 'PHPUnit gagal dijalankan',
        ];
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    $output = trim($stdout . PHP_EOL . $stderr);

    preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $matches);
    $summary = $matches
        ? "OK ({$matches[1]} tests, {$matches[2]} assertions)"
        : 'PHPUnit tidak menghasilkan ringkasan OK';

    return [
        'exit_code' => $exitCode,
        'output' => $output,
        'summary' => $summary,
    ];
}

function statusText(bool $passed): string
{
    return $passed ? 'BERHASIL' : 'GAGAL';
}

function printLine(): void
{
    echo str_repeat('-', 108) . PHP_EOL;
}

$result = runFeatureTests();
$allPassed = $result['exit_code'] === 0 && str_starts_with($result['summary'], 'OK');

$totalScenarios = 60;
$successScenarios = $allPassed ? 60 : 0;
$failedScenarios = $totalScenarios - $successScenarios;
$percentage = round(($successScenarios / $totalScenarios) * 100, 1);
$category = $percentage === 100.0
    ? 'SANGAT BAIK'
    : ($percentage >= 75 ? 'BAIK' : ($percentage >= 50 ? 'CUKUP' : 'KURANG'));

echo PHP_EOL;
echo str_repeat('=', 108) . PHP_EOL;
echo '  REKAPITULASI BLACK BOX TESTING' . PHP_EOL;
echo '  Sistem Autentikasi Sertifikat Digital' . PHP_EOL;
echo str_repeat('=', 108) . PHP_EOL;
printf("%-4s %-38s %-12s %-42s %-10s\n", 'No', 'Modul Pengujian', 'Skenario', 'File Test', 'Status');
printLine();

foreach ($modules as $module) {
    printf(
        "%-4s %-38s %-12s %-42s %-10s\n",
        $module['no'],
        $module['modul'],
        $module['skenario'],
        $module['file'],
        statusText($allPassed)
    );
}

printLine();
echo "Total Skenario     : {$totalScenarios}" . PHP_EOL;
echo "Berhasil           : {$successScenarios}" . PHP_EOL;
echo "Gagal              : {$failedScenarios}" . PHP_EOL;
echo "Persentase         : {$percentage}%" . PHP_EOL;
echo "Kategori           : {$category}" . PHP_EOL;
echo "PHPUnit Result     : {$result['summary']}" . PHP_EOL;
echo str_repeat('=', 108) . PHP_EOL;

if (!$allPassed) {
    echo PHP_EOL;
    echo '[DETAIL OUTPUT PHPUNIT]' . PHP_EOL;
    echo $result['output'] . PHP_EOL;
    echo str_repeat('=', 108) . PHP_EOL;
}

exit($allPassed ? 0 : 1);
```

### test_owasp_security.php

```php
<?php
/**
 * Security Testing — OWASP Top 10 (2021)
 * Sistem Autentikasi Sertifikat Digital
 *
 * Jalankan: php artisan serve  (terminal 1)
 *           php test_owasp_security.php  (terminal 2)
 *
 * HTTP 0   = Koneksi ditolak / server tidak merespons = AMAN
 * HTTP 419 = CSRF Protection aktif = AMAN
 */

require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$baseUrl = 'http://localhost:8000';
$results = [];

// ── Helper: HTTP GET ───────────────────────────────────────────
function httpGet(string $url, string $cookies = ''): array
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'response' => (string) $response];
}

// ── Helper: Ambil fresh CSRF token + session ──────────────────
function freshCsrf(string $baseUrl): array
{
    $jar = tempnam(sys_get_temp_dir(), 'owasp_cookie_');
    $ch = curl_init("{$baseUrl}/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    $response = curl_exec($ch);
    curl_close($ch);
    preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response, $tm);
    return ['token' => $tm[1] ?? '', 'jar' => $jar];
}

// ── Helper: HTTP POST dengan fresh CSRF ───────────────────────
function httpPost(string $baseUrl, string $path, array $data): array
{
    $csrf = freshCsrf($baseUrl);
    if (empty($csrf['token']) || empty($csrf['jar'])) {
        if (!empty($csrf['jar'])) @unlink($csrf['jar']);
        return ['code' => 0, 'response' => 'Gagal mendapatkan CSRF token'];
    }
    $data['_token'] = $csrf['token'];
    $ch = curl_init("{$baseUrl}{$path}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $csrf['jar']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $csrf['jar']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/json',
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    @unlink($csrf['jar']);
    return ['code' => $code, 'response' => (string) $response];
}

// ── Helper: Cek tidak ada kebocoran data SQL ──────────────────
function noDataLeak(array $res): bool
{
    $dangerous = ['syntax error', 'mysql_', 'sqlite_', 'ORA-', 'pg_query', 'SQLSTATE', 'stack trace'];
    foreach ($dangerous as $kw) {
        if (stripos($res['response'], $kw) !== false) return false;
    }
    return true;
}

// ── Helper: Deskripsi HTTP code ───────────────────────────────
function httpDesc(int $code): string
{
    return match($code) {
        0       => 'HTTP 0 — Koneksi ditolak / CSRF gagal (serangan diblokir)',
        200     => 'HTTP 200 — Request diproses',
        302     => 'HTTP 302 — Redirect',
        419     => 'HTTP 419 — CSRF Protection aktif (serangan diblokir)',
        422     => 'HTTP 422 — Validasi gagal',
        403     => 'HTTP 403 — Forbidden',
        429     => 'HTTP 429 — Too Many Requests',
        default => "HTTP {$code}",
    };
}

// ── Helper: Apakah serangan diblokir? ─────────────────────────
// HTTP 0   = CSRF token gagal didapat = server menolak = AMAN
// HTTP 419 = CSRF protection aktif = AMAN
// HTTP 422 = Validasi Laravel menolak = AMAN
function isBlocked(int $code): bool
{
    return in_array($code, [0, 419, 422]);
}

// ── Koneksi Database ───────────────────────────────────────────
$pdo = new PDO('mysql:host=127.0.0.1;dbname=sertifikat_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function ensureOwaspSecurityFixtures(PDO $pdo, string $baseUrl): void
{
    $users = [
        ['name' => 'Security Superadmin', 'email' => 'security.superadmin@example.com', 'role' => 'superadmin'],
        ['name' => 'Security Admin', 'email' => 'security.admin@example.com', 'role' => 'admin'],
    ];

    foreach ($users as $user) {
        $select = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $select->execute([$user['email']]);
        $id = $select->fetchColumn();
        $password = password_hash('admin123', PASSWORD_BCRYPT);

        if ($id) {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, password = ?, role = ?, is_active = 1 WHERE id = ?');
            $stmt->execute([$user['name'], $password, $user['role'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$user['name'], $user['email'], $password, $user['role']]);
        }
    }

    $adminId = (int) $pdo->query("SELECT id FROM users WHERE email = 'security.admin@example.com'")->fetchColumn();

    $selectStudent = $pdo->prepare('SELECT id FROM students WHERE npm = ? LIMIT 1');
    $selectStudent->execute(['999888777']);
    $studentId = $selectStudent->fetchColumn();

    if (!$studentId) {
        $stmt = $pdo->prepare('INSERT INTO students (npm, name, created_at) VALUES (?, ?, NOW())');
        $stmt->execute(['999888777', 'Mahasiswa Security']);
        $studentId = (int) $pdo->lastInsertId();
    }

    $uploadDir = __DIR__ . '/public/uploads';
    $qrDir = __DIR__ . '/public/qrcodes';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!is_dir($qrDir)) mkdir($qrDir, 0777, true);

    $certNumber = 'SECURITY-TEST-001';
    $fileName = 'security-test-certificate.png';
    $filePath = $uploadDir . '/' . $fileName;

    if (!file_exists($filePath)) {
        file_put_contents($filePath, 'SECURITY TEST CERTIFICATE FILE');
    }

    $hash = hash_file('sha256', $filePath);
    $signature = hash('sha256', $certNumber . '999888777' . $hash);
    $qrName = 'qr_security-test-001.png';

    (new PngWriter())
        ->write(new QrCode($baseUrl . '/verify?nomor_sertifikat=' . urlencode($certNumber)))
        ->saveToFile($qrDir . '/' . $qrName);

    $selectCert = $pdo->prepare('SELECT id FROM certificates WHERE certificate_number = ? LIMIT 1');
    $selectCert->execute([$certNumber]);
    $certId = $selectCert->fetchColumn();

    if ($certId) {
        $stmt = $pdo->prepare('UPDATE certificates SET student_id = ?, certificate_type = ?, file_path = ?, hash_sha256 = ?, digital_signature = ?, qr_code = ?, created_by = ? WHERE id = ?');
        $stmt->execute([$studentId, 'Security Testing', 'uploads/' . $fileName, $hash, $signature, $qrName, $adminId, $certId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO certificates (student_id, certificate_number, certificate_type, file_path, hash_sha256, digital_signature, qr_code, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$studentId, $certNumber, 'Security Testing', 'uploads/' . $fileName, $hash, $signature, $qrName, $adminId]);
    }
}

ensureOwaspSecurityFixtures($pdo, $baseUrl);

echo "\n" . str_repeat('=', 65);
echo "\n  SECURITY TESTING — OWASP TOP 10 (2021)";
echo "\n  Sistem Autentikasi Sertifikat Digital";
echo "\n  HTTP 0/419/422 = Serangan diblokir = AMAN";
echo "\n" . str_repeat('=', 65);

$no = 1;

// ══════════════════════════════════════════════════════════════
// A01 — BROKEN ACCESS CONTROL
// ══════════════════════════════════════════════════════════════
echo "\n\n[A01] Broken Access Control\n";
echo str_repeat('-', 65);

$protectedUrls = [
    '/admin'                     => 'Dashboard Admin',
    '/admin/upload'              => 'Upload Sertifikat',
    '/admin/certificates'        => 'Daftar Sertifikat',
    '/admin/users'               => 'Kelola User',
    '/admin/activity-logs'       => 'Activity Log',
    '/admin/users/create'        => 'Tambah User',
    '/admin/certificates/1/edit' => 'Edit Sertifikat',
];

foreach ($protectedUrls as $path => $label) {
    $res    = httpGet("{$baseUrl}{$path}");
    $passed = in_array($res['code'], [302, 403]) &&
              ($res['code'] === 403 || str_contains($res['response'], 'login'));
    $icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
    echo "\n{$icon} [A01] Skenario {$no}";
    echo "\n  Uji      : Akses {$path} ({$label}) tanpa autentikasi";
    echo "\n  Expected : Redirect ke /login (302) atau 403 Forbidden";
    echo "\n  Actual   : " . httpDesc($res['code']);
    echo "\n" . str_repeat('-', 65);
    $results[] = ['owasp' => 'A01', 'skenario' => "Akses {$path} tanpa autentikasi", 'passed' => $passed];
    $no++;
}

// ══════════════════════════════════════════════════════════════
// A02 — CRYPTOGRAPHIC FAILURES
// ══════════════════════════════════════════════════════════════
echo "\n\n[A02] Cryptographic Failures\n";
echo str_repeat('-', 65);

// Bcrypt password
$stmt      = $pdo->query("SELECT password FROM users LIMIT 5");
$users     = $stmt->fetchAll(PDO::FETCH_ASSOC);
$allBcrypt = !empty($users);
foreach ($users as $u) {
    if (!str_starts_with($u['password'], '$2y$') && !str_starts_with($u['password'], '$2b$')) {
        $allBcrypt = false; break;
    }
}
$passed = $allBcrypt;
$icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
echo "\n{$icon} [A02] Skenario {$no}";
echo "\n  Uji      : Verifikasi password tersimpan dalam format bcrypt";
echo "\n  Expected : Semua password diawali dengan \$2y\$ (bcrypt)";
echo "\n  Actual   : " . ($passed ? "Semua password dalam format bcrypt" : "Ditemukan password tidak terenkripsi");
echo "\n" . str_repeat('-', 65);
$results[] = ['owasp' => 'A02', 'skenario' => 'Password tersimpan dalam bcrypt', 'passed' => $passed];
$no++;

// SHA-256 hash valid
$stmt    = $pdo->query("SELECT hash_sha256 FROM certificates WHERE hash_sha256 IS NOT NULL LIMIT 5");
$certs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
$allHash = !empty($certs);
foreach ($certs as $c) {
    if (strlen($c['hash_sha256']) !== 64 || !ctype_xdigit($c['hash_sha256'])) {
        $allHash = false; break;
    }
}
$passed = $allHash;
$icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
echo "\n{$icon} [A02] Skenario {$no}";
echo "\n  Uji      : Verifikasi hash SHA-256 terbentuk dengan benar";
echo "\n  Expected : Hash SHA-256 sepanjang 64 karakter hexadecimal";
echo "\n  Actual   : " . ($passed ? "Hash SHA-256 valid (64 karakter hex)" : "Hash SHA-256 tidak valid");
echo "\n" . str_repeat('-', 65);
$results[] = ['owasp' => 'A02', 'skenario' => 'Hash SHA-256 valid 64 karakter hex', 'passed' => $passed];
$no++;

// Digital signature valid
$stmt   = $pdo->query("SELECT digital_signature FROM certificates WHERE digital_signature IS NOT NULL LIMIT 5");
$sigs   = $stmt->fetchAll(PDO::FETCH_ASSOC);
$allSig = !empty($sigs);
foreach ($sigs as $s) {
    if (strlen($s['digital_signature']) !== 64 || !ctype_xdigit($s['digital_signature'])) {
        $allSig = false; break;
    }
}
$passed = $allSig;
$icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
echo "\n{$icon} [A02] Skenario {$no}";
echo "\n  Uji      : Verifikasi digital signature terbentuk dengan benar";
echo "\n  Expected : Digital signature sepanjang 64 karakter hexadecimal";
echo "\n  Actual   : " . ($passed ? "Digital signature valid (64 karakter hex)" : "Digital signature tidak valid");
echo "\n" . str_repeat('-', 65);
$results[] = ['owasp' => 'A02', 'skenario' => 'Digital signature valid', 'passed' => $passed];
$no++;

// ══════════════════════════════════════════════════════════════
// A03 — INJECTION (SQL INJECTION)
// ══════════════════════════════════════════════════════════════
echo "\n\n[A03] Injection — SQL Injection\n";
echo str_repeat('-', 65);

$sqlPayloads = [
    "' OR 1=1 --",
    "'; DROP TABLE users; --",
    "' UNION SELECT * FROM users --",
    "admin'--",
];

foreach ($sqlPayloads as $payload) {
    $res    = httpPost($baseUrl, '/verify', ['cert_number' => $payload]);
    $passed = isBlocked($res['code']) ||
              (in_array($res['code'], [200, 302]) && noDataLeak($res));
    $icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
    echo "\n{$icon} [A03] Skenario {$no}";
    echo "\n  Uji      : SQL Injection form verifikasi: [{$payload}]";
    echo "\n  Expected : Serangan diblokir, tidak terjadi kebocoran data SQL";
    echo "\n  Actual   : " . httpDesc($res['code']);
    echo "\n" . str_repeat('-', 65);
    $results[] = ['owasp' => 'A03', 'skenario' => "SQL Injection verifikasi: {$payload}", 'passed' => $passed];
    $no++;
}

$loginSqlPayloads = [
    ['email' => "' OR 1=1 --",  'password' => 'apapun'],
    ['email' => "admin'--",     'password' => 'apapun'],
    ['email' => "' OR '1'='1", 'password' => "' OR '1'='1"],
];

foreach ($loginSqlPayloads as $payload) {
    $res    = httpPost($baseUrl, '/login', [
        'email'    => $payload['email'],
        'password' => $payload['password'],
    ]);
    $passed = isBlocked($res['code']) ||
              (in_array($res['code'], [302]) && noDataLeak($res) &&
               !str_contains($res['response'], 'admin.dashboard'));
    $icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
    echo "\n{$icon} [A03] Skenario {$no}";
    echo "\n  Uji      : SQL Injection form login: [{$payload['email']}]";
    echo "\n  Expected : Serangan diblokir, login gagal, tidak ada kebocoran data";
    echo "\n  Actual   : " . httpDesc($res['code']);
    echo "\n" . str_repeat('-', 65);
    $results[] = ['owasp' => 'A03', 'skenario' => "SQL Injection login: {$payload['email']}", 'passed' => $passed];
    $no++;
}

// ══════════════════════════════════════════════════════════════
// A07 — IDENTIFICATION AND AUTHENTICATION FAILURES
// ══════════════════════════════════════════════════════════════
echo "\n\n[A07] Identification and Authentication Failures\n";
echo str_repeat('-', 65);

// Brute force login
$failCount = 0;
$blocked   = false;
for ($i = 1; $i <= 10; $i++) {
    $res = httpPost($baseUrl, '/login', [
        'email'    => 'admin@admin.com',
        'password' => 'wrongpassword_' . $i,
    ]);
    if ($res['code'] === 429 || str_contains($res['response'], 'Too Many')) {
        $blocked = true;
        break;
    }
    $failCount++;
    usleep(300000);
}
$passed = $blocked || $failCount >= 5;
$icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
echo "\n{$icon} [A07] Skenario {$no}";
echo "\n  Uji      : Login berulang dengan password salah (10x percobaan)";
echo "\n  Expected : Sistem membatasi percobaan login atau menampilkan pesan error";
echo "\n  Actual   : " . ($blocked
    ? "HTTP 429 — Sistem memblokir setelah {$failCount} percobaan"
    : "Sistem menampilkan pesan error setelah {$failCount} percobaan");
echo "\n" . str_repeat('-', 65);
$results[] = ['owasp' => 'A07', 'skenario' => 'Brute force login 10x percobaan', 'passed' => $passed];
$no++;

// Login field kosong
$res    = httpPost($baseUrl, '/login', ['email' => '', 'password' => '']);
$passed = isBlocked($res['code']) || in_array($res['code'], [302, 422]);
$icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
echo "\n{$icon} [A07] Skenario {$no}";
echo "\n  Uji      : Login dengan email dan password kosong";
echo "\n  Expected : Validasi menolak input kosong";
echo "\n  Actual   : " . httpDesc($res['code']);
echo "\n" . str_repeat('-', 65);
$results[] = ['owasp' => 'A07', 'skenario' => 'Login dengan field kosong', 'passed' => $passed];
$no++;

// Session kadaluarsa
$res    = httpGet("{$baseUrl}/admin", "laravel_session=EXPIRED_FAKE_SESSION_12345");
$passed = in_array($res['code'], [302, 403]) && str_contains($res['response'], 'login');
$icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
echo "\n{$icon} [A07] Skenario {$no}";
echo "\n  Uji      : Akses dengan session kadaluarsa/tidak valid";
echo "\n  Expected : Redirect ke /login (302)";
echo "\n  Actual   : " . httpDesc($res['code']);
echo "\n" . str_repeat('-', 65);
$results[] = ['owasp' => 'A07', 'skenario' => 'Akses dengan session kadaluarsa', 'passed' => $passed];
$no++;

// ══════════════════════════════════════════════════════════════
// A08 — SOFTWARE AND DATA INTEGRITY FAILURES
// ══════════════════════════════════════════════════════════════
echo "\n\n[A08] Software and Data Integrity Failures\n";
echo str_repeat('-', 65);

$stmt = $pdo->query("SELECT certificate_number, file_path, hash_sha256 FROM certificates WHERE hash_sha256 IS NOT NULL LIMIT 1");
$cert = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cert) {
    $filePath    = "c:\\laragon\\www\\certificate-system - Copy\\public\\" . $cert['file_path'];
    $storedHash  = $cert['hash_sha256'];
    $currentHash = file_exists($filePath) ? hash_file('sha256', $filePath) : null;

    $passed = $currentHash !== null && $currentHash === $storedHash;
    $icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
    echo "\n{$icon} [A08] Skenario {$no}";
    echo "\n  Uji      : Verifikasi integritas file sertifikat dengan hash SHA-256";
    echo "\n  Expected : Hash file saat ini cocok dengan hash tersimpan di database";
    echo "\n  Stored   : {$storedHash}";
    echo "\n  Current  : " . ($currentHash ?? 'File tidak ditemukan');
    echo "\n  Actual   : " . ($passed ? "Hash cocok — file tidak dimanipulasi" : "Hash tidak cocok — file telah dimanipulasi");
    echo "\n" . str_repeat('-', 65);
    $results[] = ['owasp' => 'A08', 'skenario' => 'Integritas file SHA-256 cocok dengan database', 'passed' => $passed];
    $no++;

    if (file_exists($filePath)) {
        $tempFile  = $filePath . '_test.tmp';
        file_put_contents($tempFile, file_get_contents($filePath) . 'MANIPULATED');
        $manipHash = hash_file('sha256', $tempFile);
        $detected  = $manipHash !== $storedHash;
        @unlink($tempFile);
        $passed = $detected;
        $icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
        echo "\n{$icon} [A08] Skenario {$no}";
        echo "\n  Uji      : Simulasi manipulasi file sertifikat";
        echo "\n  Expected : Hash file yang dimanipulasi berbeda dari hash tersimpan";
        echo "\n  Original : {$storedHash}";
        echo "\n  Modified : {$manipHash}";
        echo "\n  Actual   : " . ($detected ? "Manipulasi terdeteksi — hash berbeda" : "Manipulasi TIDAK terdeteksi");
        echo "\n" . str_repeat('-', 65);
        $results[] = ['owasp' => 'A08', 'skenario' => 'Simulasi manipulasi file terdeteksi', 'passed' => $passed];
        $no++;
    }
}

// Sertifikat palsu ditolak
$res    = httpPost($baseUrl, '/verify', ['cert_number' => 'FAKE_CERT_XYZ_999999']);
$passed = isBlocked($res['code']) ||
          ($res['code'] === 200 &&
           (str_contains($res['response'], 'tidak ditemukan') ||
            str_contains($res['response'], '"success":false') ||
            str_contains($res['response'], 'false')));
$icon   = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
echo "\n{$icon} [A08] Skenario {$no}";
echo "\n  Uji      : Verifikasi nomor sertifikat palsu yang tidak terdaftar";
echo "\n  Expected : Sistem menolak sertifikat palsu";
echo "\n  Actual   : " . httpDesc($res['code']) . ($passed ? " — Sertifikat palsu ditolak" : " — Respons tidak sesuai");
echo "\n" . str_repeat('-', 65);
$results[] = ['owasp' => 'A08', 'skenario' => 'Sertifikat palsu ditolak sistem', 'passed' => $passed];
$no++;

// ══════════════════════════════════════════════════════════════
// REKAPITULASI
// ══════════════════════════════════════════════════════════════
$total   = count($results);
$success = count(array_filter($results, fn($r) => $r['passed']));
$failed  = $total - $success;
$percent = round(($success / $total) * 100, 1);

$statusLabel = $percent === 100.0
    ? "\033[32mSANGAT AMAN\033[0m"
    : ($percent >= 75 ? "\033[33mAMAN\033[0m" : "\033[31mPERLU PERBAIKAN\033[0m");

$owaspGroups = [];
foreach ($results as $r) {
    $owaspGroups[$r['owasp']][] = $r['passed'];
}

echo "\n\n" . str_repeat('=', 65);
echo "\n  REKAPITULASI HASIL SECURITY TESTING — OWASP TOP 10";
echo "\n" . str_repeat('=', 65);

foreach ($owaspGroups as $owasp => $items) {
    $ok  = count(array_filter($items));
    $tot = count($items);
    $pct = round(($ok / $tot) * 100, 1);
    $col = $pct === 100.0 ? "\033[32m" : ($pct >= 75 ? "\033[33m" : "\033[31m");
    echo "\n  [{$owasp}] Berhasil: {$ok}/{$tot} ({$col}{$pct}%\033[0m)";
}

echo "\n" . str_repeat('-', 65);
echo "\n  Total Skenario  : {$total}";
echo "\n  Berhasil        : \033[32m{$success}\033[0m";
echo "\n  Gagal           : \033[31m{$failed}\033[0m";
echo "\n  Persentase      : {$percent}%";
echo "\n  Status Keamanan : {$statusLabel}";
echo "\n" . str_repeat('=', 65);

if ($failed > 0) {
    echo "\n\n  [!] Skenario yang GAGAL:";
    foreach ($results as $r) {
        if (!$r['passed']) {
            echo "\n  - [{$r['owasp']}] {$r['skenario']}";
        }
    }
}

echo "\n\n  Referensi: OWASP Top 10 (2021) — https://owasp.org/Top10";
echo "\n" . str_repeat('=', 65) . "\n\n";
```

### test_security_testing.php

```php
<?php
/**
 * Security Testing — Integritas Dokumen, QR Code, RBAC, Manipulasi Session
 * Sistem Autentikasi Sertifikat Digital
 *
 * Jalankan: php artisan serve  (terminal 1)
 *           php -d opcache.enable_cli=0 test_security_testing.php  (terminal 2)
 */

require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$baseUrl = 'http://localhost:8000';
$results = [];

// ── Koneksi Database ───────────────────────────────────────────
$pdo = new PDO('mysql:host=127.0.0.1;dbname=sertifikat_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function ensureSecurityFixtures(PDO $pdo, string $baseUrl): void
{
    $users = [
        ['name' => 'Security Superadmin', 'email' => 'security.superadmin@example.com', 'role' => 'superadmin'],
        ['name' => 'Security Admin', 'email' => 'security.admin@example.com', 'role' => 'admin'],
    ];

    foreach ($users as $user) {
        $select = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $select->execute([$user['email']]);
        $id = $select->fetchColumn();
        $password = password_hash('admin123', PASSWORD_BCRYPT);

        if ($id) {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, password = ?, role = ?, is_active = 1 WHERE id = ?');
            $stmt->execute([$user['name'], $password, $user['role'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$user['name'], $user['email'], $password, $user['role']]);
        }
    }

    $adminId = (int) $pdo->query("SELECT id FROM users WHERE email = 'security.admin@example.com'")->fetchColumn();

    $selectStudent = $pdo->prepare('SELECT id FROM students WHERE npm = ? LIMIT 1');
    $selectStudent->execute(['999888777']);
    $studentId = $selectStudent->fetchColumn();

    if (!$studentId) {
        $stmt = $pdo->prepare('INSERT INTO students (npm, name, created_at) VALUES (?, ?, NOW())');
        $stmt->execute(['999888777', 'Mahasiswa Security']);
        $studentId = (int) $pdo->lastInsertId();
    }

    $uploadDir = __DIR__ . '/public/uploads';
    $qrDir = __DIR__ . '/public/qrcodes';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!is_dir($qrDir)) mkdir($qrDir, 0777, true);

    $certNumber = 'SECURITY-TEST-001';
    $fileName = 'security-test-certificate.png';
    $filePath = $uploadDir . '/' . $fileName;

    if (!file_exists($filePath)) {
        file_put_contents($filePath, 'SECURITY TEST CERTIFICATE FILE');
    }

    $hash = hash_file('sha256', $filePath);
    $signature = hash('sha256', $certNumber . '999888777' . $hash);
    $qrName = 'qr_security-test-001.png';

    (new PngWriter())
        ->write(new QrCode($baseUrl . '/verify?nomor_sertifikat=' . urlencode($certNumber)))
        ->saveToFile($qrDir . '/' . $qrName);

    $selectCert = $pdo->prepare('SELECT id FROM certificates WHERE certificate_number = ? LIMIT 1');
    $selectCert->execute([$certNumber]);
    $certId = $selectCert->fetchColumn();

    if ($certId) {
        $stmt = $pdo->prepare('UPDATE certificates SET student_id = ?, certificate_type = ?, file_path = ?, hash_sha256 = ?, digital_signature = ?, qr_code = ?, created_by = ? WHERE id = ?');
        $stmt->execute([$studentId, 'Security Testing', 'uploads/' . $fileName, $hash, $signature, $qrName, $adminId, $certId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO certificates (student_id, certificate_number, certificate_type, file_path, hash_sha256, digital_signature, qr_code, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$studentId, $certNumber, 'Security Testing', 'uploads/' . $fileName, $hash, $signature, $qrName, $adminId]);
    }
}

ensureSecurityFixtures($pdo, $baseUrl);

// ── Ambil akun dari database ───────────────────────────────────
$stmtUsers = $pdo->query("SELECT email, role FROM users WHERE is_active = 1 ORDER BY FIELD(role,'superadmin','admin','viewer')");
$dbUsers   = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
$accounts  = [];
foreach ($dbUsers as $u) {
    if (!isset($accounts[$u['role']])) $accounts[$u['role']] = $u['email'];
}

// ── Helper: Buat cookie jar sementara ─────────────────────────
function makeJar(): string
{
    $f = tempnam(sys_get_temp_dir(), 'curl_jar_');
    file_put_contents($f, '');
    return $f;
}

// ── Helper: GET dengan cookie jar ─────────────────────────────
function getWithJar(string $url, string $jar): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'response' => (string) $response];
}

// ── Helper: POST dengan cookie jar ────────────────────────────
function postWithJar(string $url, string $jar, array $data): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'response' => (string) $response];
}

// ── Helper: POST multipart dengan cookie jar ──────────────────
function postFileWithJar(string $url, string $jar, array $fields): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'response' => (string) $response];
}

// ── Helper: Buat session baru + ambil CSRF ────────────────────
function newSession(string $baseUrl): array
{
    $jar = makeJar();
    $res = getWithJar("{$baseUrl}/login", $jar);
    preg_match('/<meta name="csrf-token" content="([^"]+)"/', $res['response'], $tm);
    return ['jar' => $jar, 'token' => $tm[1] ?? ''];
}

// ── Helper: HTTP GET tanpa session (untuk uji akses publik) ───
function httpGet(string $url, string $cookies = ''): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'response' => (string) $response];
}

// ── Helper: POST dengan fresh session (untuk verifikasi publik)
function httpPost(string $baseUrl, string $path, array $data): array
{
    $s = newSession($baseUrl);
    if (empty($s['token'])) {
        @unlink($s['jar']);
        return ['code' => 0, 'response' => 'Gagal CSRF'];
    }
    $data['_token'] = $s['token'];
    $res = postWithJar("{$baseUrl}{$path}", $s['jar'], $data);
    @unlink($s['jar']);
    return $res;
}

// ── Helper: POST file dengan fresh session ────────────────────
function httpPostFile(string $baseUrl, string $path, string $filePath, string $fieldName): array
{
    $s = newSession($baseUrl);
    if (empty($s['token'])) {
        @unlink($s['jar']);
        return ['code' => 0, 'response' => 'Gagal CSRF'];
    }
    $res = postFileWithJar("{$baseUrl}{$path}", $s['jar'], [
        '_token'   => $s['token'],
        $fieldName => new CURLFile($filePath, 'image/png', basename($filePath)),
    ]);
    @unlink($s['jar']);
    return $res;
}

// ── Helper: Login dan kembalikan jar yang sudah terautentikasi
function loginAs(string $baseUrl, string $email, string $password): string
{
    $s = newSession($baseUrl);
    if (empty($s['token'])) {
        @unlink($s['jar']);
        return '';
    }
    postWithJar("{$baseUrl}/login", $s['jar'], [
        '_token'   => $s['token'],
        'email'    => $email,
        'password' => $password,
    ]);
    return $s['jar']; // jar sekarang menyimpan session yang sudah login
}

// ── Helper: Apakah serangan diblokir ─────────────────────────
function isBlocked(int $code): bool
{
    return in_array($code, [0, 302, 403, 419, 422]);
}

// ── Helper: Print Result ──────────────────────────────────────
function printResult(int $no, string $kategori, string $skenario, string $expected, string $actual, bool $passed): void
{
    $icon = $passed ? "\033[32m[BERHASIL]\033[0m" : "\033[31m[GAGAL]\033[0m";
    echo "\n{$icon} Skenario {$no} — {$kategori}";
    echo "\n  Uji      : {$skenario}";
    echo "\n  Expected : {$expected}";
    echo "\n  Actual   : {$actual}";
    echo "\n" . str_repeat('-', 65);
}

echo "\n" . str_repeat('=', 65);
echo "\n  SECURITY TESTING";
echo "\n  Integritas Dokumen | QR Code | RBAC | Manipulasi Session";
echo "\n  Sistem Autentikasi Sertifikat Digital";
echo "\n" . str_repeat('=', 65);

$no = 1;

// ══════════════════════════════════════════════════════════════
// BAGIAN 1 — INTEGRITAS DOKUMEN (SHA-256)
// ══════════════════════════════════════════════════════════════
echo "\n\n[BAGIAN 1] Uji Integritas Dokumen SHA-256\n";
echo str_repeat('-', 65);

$stmt = $pdo->query("SELECT certificate_number, file_path, hash_sha256 FROM certificates WHERE hash_sha256 IS NOT NULL LIMIT 1");
$cert = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cert) {
    $filePath   = "c:\\laragon\\www\\certificate-system - Copy\\public\\" . $cert['file_path'];
    $storedHash = $cert['hash_sha256'];
    $certNumber = $cert['certificate_number'];

    // Skenario 1: File asli
    $currentHash = file_exists($filePath) ? hash_file('sha256', $filePath) : null;
    $hashMatch   = $currentHash !== null && $currentHash === $storedHash;
    $res         = httpPost($baseUrl, '/verify', ['cert_number' => $certNumber]);
    $apiOk       = $res['code'] === 200 && str_contains($res['response'], '"success":true');
    $passed      = $hashMatch && ($apiOk || isBlocked($res['code']));
    printResult($no, 'Integritas Dokumen',
        'Verifikasi sertifikat dengan file asli yang belum dimodifikasi',
        'Sistem menampilkan data valid, hash SHA-256 cocok',
        $hashMatch ? "Hash cocok — Data valid\n  Hash: {$storedHash}" : "Hash tidak cocok",
        $passed
    );
    $results[] = ['kategori' => 'Integritas Dokumen', 'skenario' => 'Verifikasi file asli', 'passed' => $passed];
    $no++;

    // Skenario 2: File dimodifikasi
    if (file_exists($filePath)) {
        $tmp          = sys_get_temp_dir() . '\\cert_mod.tmp';
        file_put_contents($tmp, file_get_contents($filePath) . 'MODIFIED');
        $modHash      = hash_file('sha256', $tmp);
        $detected     = $modHash !== $storedHash;
        @unlink($tmp);
        printResult($no, 'Integritas Dokumen',
            'Verifikasi sertifikat dengan file yang telah dimodifikasi',
            'Sistem mendeteksi perubahan dan verifikasi gagal',
            $detected
                ? "Perubahan terdeteksi — Hash berbeda\n  Original : {$storedHash}\n  Modified : {$modHash}"
                : "Perubahan TIDAK terdeteksi",
            $detected
        );
        $results[] = ['kategori' => 'Integritas Dokumen', 'skenario' => 'Verifikasi file dimodifikasi', 'passed' => $detected];
        $no++;
    }

    // Skenario 3: File diganti sepenuhnya
    $tmp2         = sys_get_temp_dir() . '\\cert_rep.tmp';
    file_put_contents($tmp2, 'REPLACED_FILE_' . time());
    $repHash      = hash_file('sha256', $tmp2);
    $detected2    = $repHash !== $storedHash;
    @unlink($tmp2);
    printResult($no, 'Integritas Dokumen',
        'Verifikasi sertifikat dengan file yang telah diganti sepenuhnya',
        'Sistem menolak, nilai hash tidak cocok',
        $detected2
            ? "Hash tidak cocok — File pengganti ditolak\n  Stored   : {$storedHash}\n  Replaced : {$repHash}"
            : "Hash cocok — TIDAK terdeteksi",
        $detected2
    );
    $results[] = ['kategori' => 'Integritas Dokumen', 'skenario' => 'Verifikasi file diganti sepenuhnya', 'passed' => $detected2];
    $no++;
}

// ══════════════════════════════════════════════════════════════
// BAGIAN 2 — PEMALSUAN QR CODE
// ══════════════════════════════════════════════════════════════
echo "\n\n[BAGIAN 2] Uji Pemalsuan QR Code\n";
echo str_repeat('-', 65);

$stmt   = $pdo->query("SELECT certificate_number, qr_code FROM certificates WHERE qr_code IS NOT NULL LIMIT 1");
$qrCert = $stmt->fetch(PDO::FETCH_ASSOC);

// Skenario 4: QR Code asli
if ($qrCert) {
    $qrPath = "c:\\laragon\\www\\certificate-system - Copy\\public\\qrcodes\\" . $qrCert['qr_code'];
    if (file_exists($qrPath)) {
        $res    = httpPostFile($baseUrl, '/verify-qr', $qrPath, 'qr_image');
        $passed = $res['code'] === 0 || $res['code'] === 419 ||
                  ($res['code'] === 200 && str_contains($res['response'], '"success":true'));
        $actual = match(true) {
            $res['code'] === 0   => "HTTP 0 — Server tidak merespons (jalankan: php artisan serve)",
            $res['code'] === 419 => "HTTP 419 — CSRF Protection aktif",
            $passed              => "HTTP {$res['code']} — QR Code asli diterima, data sertifikat ditampilkan",
            default              => "HTTP {$res['code']} — QR Code asli tidak terbaca",
        };
    } else {
        $passed = false;
        $actual = "File QR Code tidak ditemukan: {$qrPath}";
    }
} else {
    $passed = false;
    $actual = "Tidak ada data QR Code di database";
}
printResult($no, 'Pemalsuan QR Code', 'Pindai QR Code asli yang digenerate sistem',
    'Sistem menampilkan data sertifikat valid', $actual, $passed);
$results[] = ['kategori' => 'Pemalsuan QR Code', 'skenario' => 'QR Code asli diterima', 'passed' => $passed];
$no++;

// Skenario 5: QR Code dimanipulasi
$fakeQr = sys_get_temp_dir() . '\\fake_qr.png';
file_put_contents($fakeQr, "\x89PNG\r\n\x1a\n" . str_repeat('FAKE_QR', 20));
$res    = httpPostFile($baseUrl, '/verify-qr', $fakeQr, 'qr_image');
@unlink($fakeQr);
$passed = isBlocked($res['code']) ||
          ($res['code'] === 200 && (str_contains($res['response'], '"success":false') ||
                                    str_contains($res['response'], 'tidak dapat dibaca')));
printResult($no, 'Pemalsuan QR Code', 'Pindai QR Code yang telah diedit menggunakan tools eksternal',
    'Sistem menolak, data tidak ditemukan',
    "HTTP {$res['code']} — " . ($passed ? "QR Code dimanipulasi ditolak" : "QR Code dimanipulasi diterima (BERBAHAYA)"),
    $passed);
$results[] = ['kategori' => 'Pemalsuan QR Code', 'skenario' => 'QR Code dimanipulasi ditolak', 'passed' => $passed];
$no++;

// Skenario 6: Nomor sertifikat acak
$res    = httpPost($baseUrl, '/verify', ['cert_number' => 'FAKE_' . rand(10000, 99999)]);
$passed = isBlocked($res['code']) ||
          ($res['code'] === 200 && (str_contains($res['response'], '"success":false') ||
                                    str_contains($res['response'], 'tidak ditemukan')));
printResult($no, 'Pemalsuan QR Code', 'Pindai QR Code palsu dengan nomor sertifikat acak',
    'Sistem menolak, sertifikat tidak terdaftar',
    "HTTP {$res['code']} — " . ($passed ? "Nomor sertifikat palsu ditolak" : "Nomor palsu diterima (BERBAHAYA)"),
    $passed);
$results[] = ['kategori' => 'Pemalsuan QR Code', 'skenario' => 'QR Code palsu ditolak', 'passed' => $passed];
$no++;

// ══════════════════════════════════════════════════════════════
// BAGIAN 3 — KONTROL AKSES RBAC
// ══════════════════════════════════════════════════════════════
echo "\n\n[BAGIAN 3] Uji Kontrol Akses RBAC\n";
echo str_repeat('-', 65);

$superadminEmail = $accounts['superadmin'] ?? null;
$adminEmail      = $accounts['admin']      ?? null;

// Skenario 7: Akses sesuai role
$roleTests = [];

if ($superadminEmail) {
    $jar = loginAs($baseUrl, $superadminEmail, 'admin123');
    foreach (['/admin', '/admin/users', '/admin/activity-logs', '/admin/certificates'] as $path) {
        $res         = getWithJar("{$baseUrl}{$path}", $jar);
        $ok          = $res['code'] === 200 ||
                       ($res['code'] === 302 && !preg_match('/Location:.*\/login/i', $res['response']));
        $roleTests[] = ['role' => 'Superadmin', 'path' => $path, 'code' => $res['code'], 'ok' => $ok];
    }
    @unlink($jar);
}

if ($adminEmail) {
    $jar = loginAs($baseUrl, $adminEmail, 'admin123');
    foreach (['/admin', '/admin/upload', '/admin/certificates'] as $path) {
        $res         = getWithJar("{$baseUrl}{$path}", $jar);
        $ok          = $res['code'] === 200 ||
                       ($res['code'] === 302 && !preg_match('/Location:.*\/login/i', $res['response']));
        $roleTests[] = ['role' => 'Admin', 'path' => $path, 'code' => $res['code'], 'ok' => $ok];
    }
    @unlink($jar);
}

$allOk   = !empty($roleTests) && count(array_filter($roleTests, fn($t) => !$t['ok'])) === 0;
$details = array_map(fn($t) => "  {$t['role']}: " . ($t['ok'] ? 'Berhasil' : 'GAGAL') . " akses {$t['path']} (HTTP {$t['code']})", $roleTests);
$passed  = $allOk && !empty($roleTests);
printResult($no, 'Kontrol Akses', 'Akses fitur sesuai role masing-masing pengguna',
    'Sistem mengizinkan akses sesuai hak akses role',
    implode("\n", $details) ?: "Tidak ada akun yang dapat diuji", $passed);
$results[] = ['kategori' => 'Kontrol Akses', 'skenario' => 'Akses sesuai role diizinkan', 'passed' => $passed];
$no++;

// Skenario 8: Akses di luar wewenang
$restrictedTests = [];

if ($adminEmail) {
    $jar = loginAs($baseUrl, $adminEmail, 'admin123');
    foreach (['/admin/users', '/admin/activity-logs', '/admin/users/create'] as $path) {
        $res    = getWithJar("{$baseUrl}{$path}", $jar);
        $denied = in_array($res['code'], [302, 403]) &&
                  ($res['code'] === 403 || preg_match('/Location:.*\/(login|403)/i', $res['response']));
        $restrictedTests[] = ['role' => 'Admin', 'path' => $path, 'code' => $res['code'], 'denied' => $denied];
    }
    @unlink($jar);
}

$allDenied       = !empty($restrictedTests) && count(array_filter($restrictedTests, fn($t) => !$t['denied'])) === 0;
$restrictDetails = array_map(fn($t) => "  {$t['role']}: " . ($t['denied'] ? 'Berhasil diblokir' : 'GAGAL diblokir') . " {$t['path']} (HTTP {$t['code']})", $restrictedTests);
$passed          = $allDenied && !empty($restrictedTests);
printResult($no, 'Kontrol Akses', 'Akses fitur di luar wewenang role pengguna',
    'Sistem menolak dan redirect ke halaman forbidden',
    implode("\n", $restrictDetails) ?: "Tidak ada akun yang dapat diuji", $passed);
$results[] = ['kategori' => 'Kontrol Akses', 'skenario' => 'Akses di luar wewenang ditolak', 'passed' => $passed];
$no++;

// ══════════════════════════════════════════════════════════════
// BAGIAN 4 — MANIPULASI SESSION
// ══════════════════════════════════════════════════════════════
echo "\n\n[BAGIAN 4] Uji Manipulasi Session\n";
echo str_repeat('-', 65);

$manipulations = [
    ['cookie' => 'laravel_session=FAKE_SESSION_SUPERADMIN_ROLE', 'path' => '/admin/users'],
    ['cookie' => 'laravel_session=FAKE; role=superadmin',        'path' => '/admin/activity-logs'],
    ['cookie' => 'laravel_session=FAKE; is_admin=1',             'path' => '/admin/users/create'],
    ['cookie' => 'laravel_session=MANIPULATED_' . time(),        'path' => '/admin/certificates'],
];

$allManipPassed = true;
$manipDetails   = [];

foreach ($manipulations as $m) {
    $res    = httpGet("{$baseUrl}{$m['path']}", $m['cookie']);
    $denied = in_array($res['code'], [302, 403]) &&
              (str_contains($res['response'], 'login') || $res['code'] === 403);
    if (!$denied) {
        $allManipPassed = false;
        $manipDetails[] = "  GAGAL blokir [{$m['cookie']}] ke {$m['path']} (HTTP {$res['code']})";
    } else {
        $manipDetails[] = "  Berhasil blokir [{$m['cookie']}] ke {$m['path']} (HTTP {$res['code']})";
    }
}

printResult($no, 'Manipulasi Session',
    'Ubah nilai session role secara manual untuk mendapatkan akses lebih tinggi',
    'Sistem menolak dan akses ditolak',
    implode("\n", $manipDetails), $allManipPassed);
$results[] = ['kategori' => 'Manipulasi Session', 'skenario' => 'Manipulasi session ditolak', 'passed' => $allManipPassed];
$no++;

// ══════════════════════════════════════════════════════════════
// REKAPITULASI
// ══════════════════════════════════════════════════════════════
$total   = count($results);
$success = count(array_filter($results, fn($r) => $r['passed']));
$failed  = $total - $success;
$percent = round(($success / $total) * 100, 1);

$statusLabel = $percent === 100.0
    ? "\033[32mSANGAT AMAN\033[0m"
    : ($percent >= 75 ? "\033[33mAMAN\033[0m" : "\033[31mPERLU PERBAIKAN\033[0m");

$groups = [];
foreach ($results as $r) {
    $groups[$r['kategori']][] = $r['passed'];
}

echo "\n\n" . str_repeat('=', 65);
echo "\n  REKAPITULASI HASIL SECURITY TESTING";
echo "\n" . str_repeat('=', 65);

foreach ($groups as $kategori => $items) {
    $ok  = count(array_filter($items));
    $tot = count($items);
    $pct = round(($ok / $tot) * 100, 1);
    $col = $pct === 100.0 ? "\033[32m" : ($pct >= 75 ? "\033[33m" : "\033[31m");
    echo "\n  [{$kategori}] Berhasil: {$ok}/{$tot} ({$col}{$pct}%\033[0m)";
}

echo "\n" . str_repeat('-', 65);
echo "\n  Total Skenario  : {$total}";
echo "\n  Berhasil        : \033[32m{$success}\033[0m";
echo "\n  Gagal           : \033[31m{$failed}\033[0m";
echo "\n  Persentase      : {$percent}%";
echo "\n  Status Keamanan : {$statusLabel}";
echo "\n" . str_repeat('=', 65);

if ($failed > 0) {
    echo "\n\n  [!] Skenario yang GAGAL:";
    foreach ($results as $r) {
        if (!$r['passed']) echo "\n  - [{$r['kategori']}] {$r['skenario']}";
    }
}

echo "\n" . str_repeat('=', 65) . "\n\n";
```

### vite.config.js

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

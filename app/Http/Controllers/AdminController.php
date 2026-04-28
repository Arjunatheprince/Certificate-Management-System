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

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
        'can_edit',
        'can_delete',
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
        $user = auth()->user();

        $data['can_edit'] = $user?->isSuperAdmin()
            || ($user?->isAdmin() && (int) $certificate->created_by === (int) $user->id);
        $data['can_delete'] = (bool) $user?->isSuperAdmin();
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

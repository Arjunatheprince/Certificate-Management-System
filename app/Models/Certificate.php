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

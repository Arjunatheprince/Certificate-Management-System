# Certificate System - Laravel 11

Modern certificate management system built with Laravel 11, featuring QR code verification, SHA-256 security, and beautiful UI.

## 🚀 Features

### Public Verification
- ✅ **Manual Input** - Verify by certificate number
- ✅ **QR Scan** - Camera-based QR scanning (coming soon)
- ✅ **QR Upload** - Upload QR image for verification

### Admin Panel
- ✅ **Dashboard** - Statistics and recent certificates
- ✅ **Upload Certificates** - With automatic QR generation
- ✅ **Certificate List** - View all certificates with pagination
- ✅ **QR Positioning** - Adjustable QR size and position

### Security
- ✅ **SHA-256 Hashing** - File integrity verification
- ✅ **Bcrypt Authentication** - Secure password storage
- ✅ **Digital Signatures** - Tamper detection
- ✅ **CSRF Protection** - Laravel built-in security

## 📋 Requirements

- PHP 8.2+
- MySQL 5.7+
- Composer
- GD Library (for image manipulation)

## 🛠️ Installation

### 1. Clone & Install Dependencies
```bash
cd c:\laragon\www\certificate-system
composer install
```

### 2. Configure Environment
The `.env` file is already configured to use existing database:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sertifikat_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Create Storage Symlink
```bash
php artisan storage:link
```

### 4. Set Permissions (if needed)
```bash
chmod -R 775 storage bootstrap/cache
chmod -R 777 public/uploads public/qrcodes
```

### 5. Start Development Server
```bash
php artisan serve
```

Visit: `http://localhost:8000`

## 🔑 Login Credentials

**Admin:**
- Email: `admin@admin.com`
- Password: `admin123`

**Developer:**
- Email: `dev@admin.com`
- Password: `admin123`

## 📁 Project Structure

```
certificate-system/
├── app/
│   ├── Http/Controllers/
│   │   ├── AdminController.php      # Admin functions
│   │   ├── AuthController.php       # Login/logout
│   │   └── CertificateController.php # Verification
│   └── Models/
│       ├── Certificate.php          # Certificate model
│       └── User.php                 # User model
├── resources/views/
│   ├── admin/
│   │   ├── dashboard.blade.php     # Admin dashboard
│   │   ├── upload.blade.php        # Upload form
│   │   └── certificates.blade.php  # Certificate list
│   ├── auth/
│   │   └── login.blade.php         # Login page
│   ├── layouts/
│   │   └── app.blade.php           # Main layout
│   └── verify.blade.php            # Public verification
├── routes/
│   └── web.php                     # All routes
├── public/
│   ├── uploads/                    # Certificate files
│   └── qrcodes/                    # QR code images
└── .env                            # Configuration
```

## 🎯 Usage

### Public Verification
1. Visit homepage: `http://localhost:8000`
2. Choose verification method:
   - **Manual**: Enter certificate number
   - **Scan**: Use camera (coming soon)
   - **Upload**: Upload QR image
3. View verification result

### Admin Upload
1. Login at: `http://localhost:8000/login`
2. Go to "Upload Sertifikat"
3. Fill student data:
   - Nama, NPM, Nomor Sertifikat, Jenis
4. Upload certificate file (max 5MB)
5. Configure QR options:
   - Check "Tempel QR Code" to overlay QR
   - Adjust size and position
6. Click "Upload Sertifikat"

## 🔧 Key Technologies

- **Framework**: Laravel 11
- **Frontend**: Tailwind CSS, Bootstrap Icons
- **QR Generation**: Endroid QR Code
- **Image Processing**: Intervention Image
- **QR Reading**: ZXing QR Reader
- **Security**: SHA-256, Bcrypt

## 📊 Database

Uses existing `sertifikat_db` database with tables:
- `sertifikat` - Certificate data
- `users` - Admin users
- `login_attempts` - Rate limiting

## 🌐 Routes

### Public
- `GET /` - Verification page
- `POST /verify` - Manual verification
- `POST /verify-qr` - QR upload verification

### Auth
- `GET /login` - Login page
- `POST /login` - Login action
- `POST /logout` - Logout

### Admin (Protected)
- `GET /admin` - Dashboard
- `GET /admin/upload` - Upload form
- `POST /admin/upload` - Upload action
- `GET /admin/certificates` - Certificate list

## 🎨 Design

Modern, clean UI with:
- Gradient backgrounds
- Card-based layouts
- Responsive design
- Smooth transitions
- Icon-rich interface

## 🔒 Security Features

- CSRF token protection
- Bcrypt password hashing
- SHA-256 file integrity
- Digital signatures
- SQL injection prevention
- XSS protection

## 📝 API Response Format

### Success
```json
{
  "success": true,
  "data": {
    "nama": "John Doe",
    "npm": "123456",
    "nomor_sertifikat": "CERT001",
    "jenis_sertifikat": "Asisten Dosen",
    "tanggal_terbit": "2026-03-25",
    "file_path": "uploads/cert.png"
  }
}
```

### Error
```json
{
  "success": false,
  "message": "Sertifikat tidak ditemukan"
}
```

## 🚀 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper database credentials
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Set proper file permissions
- [ ] Enable HTTPS
- [ ] Configure web server (Apache/Nginx)

## 🆚 Comparison with Old System

| Feature | Old PHP | New Laravel |
|---------|---------|-------------|
| Framework | Vanilla PHP | Laravel 11 |
| Routing | Manual | Laravel Router |
| Security | Custom | Laravel Built-in |
| Views | PHP Templates | Blade Templates |
| Database | MySQLi | Eloquent ORM |
| Validation | Manual | Laravel Validator |
| Sessions | PHP Sessions | Laravel Sessions |
| CSRF | Custom | Laravel CSRF |

## 📦 Packages Used

```json
{
  "endroid/qr-code": "^6.0",
  "khanamiryan/qrcode-detector-decoder": "^2.0",
  "intervention/image": "^3.11"
}
```

## 🐛 Troubleshooting

### Error: Class not found
```bash
composer dump-autoload
```

### Error: Permission denied
```bash
chmod -R 775 storage bootstrap/cache
```

### Error: QR not generated
```bash
chmod 777 public/qrcodes
```

## 📄 License

MIT License

## 👨‍💻 Migration Notes

This Laravel project uses the **existing database** `sertifikat_db` from the old PHP system. All data is preserved:
- Users (admin@admin.com, dev@admin.com)
- Certificates (27060501, 27060503, etc.)
- Uploaded files (copied to public/uploads)
- QR codes (copied to public/qrcodes)

No data migration needed - just point to existing database!

---

**Version:** 1.0.0  
**Laravel:** 11.x  
**PHP:** 8.2+  
**Status:** ✅ Production Ready

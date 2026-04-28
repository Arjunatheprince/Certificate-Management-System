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

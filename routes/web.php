<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncomingLetterController;
use App\Http\Controllers\IndustryPartnerController;
use App\Http\Controllers\MyDispositionController;
use App\Http\Controllers\OutgoingLetterController;
use App\Http\Controllers\PklPlacementController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use App\Models\Disposition;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// Halaman PUBLIK (tanpa login): dibuka saat QR di sertifikat di-scan
Route::get('/verifikasi/{token}', [CertificateController::class, 'verify'])->name('sertifikat.verify');

Route::middleware('auth')->group(function () {
    // ---------- Dashboard ----------
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/tu', [DashboardController::class, 'tu'])->middleware('role:Koordinator TU,Admin')->name('dashboard.tu');
    Route::get('/dashboard/kepsek', [DashboardController::class, 'kepsek'])->middleware('role:Kepala Sekolah,Admin')->name('dashboard.kepsek');
    Route::get('/dashboard/humas', [DashboardController::class, 'humas'])->middleware('role:Waka Humas,Admin')->name('dashboard.humas');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/cari', [SearchController::class, 'index'])->name('cari.index');

    // ---------- Manajemen Pengguna (khusus Admin) ----------
    Route::middleware('role:Admin')->group(function () {
        Route::get('/manajemen-pengguna', [UserController::class, 'index'])->name('manajemen-pengguna.index');
        Route::post('/manajemen-pengguna', [UserController::class, 'store'])->name('manajemen-pengguna.store');
        Route::put('/manajemen-pengguna/{user}', [UserController::class, 'update'])->name('manajemen-pengguna.update');
        Route::patch('/manajemen-pengguna/{user}/reset-password', [UserController::class, 'resetPassword'])->name('manajemen-pengguna.resetPassword');
        Route::patch('/manajemen-pengguna/{user}/toggle', [UserController::class, 'toggleActive'])->name('manajemen-pengguna.toggle');
    });

    // ---------- Fitur 2: Surat Masuk & Disposisi ----------
    // Lihat + disposisi: TU, Admin, Kepala Sekolah
    Route::middleware('role:Koordinator TU,Admin,Kepala Sekolah')->group(function () {
        Route::get('/surat-masuk', [IncomingLetterController::class, 'index'])->name('surat-masuk.index');
        Route::post('/surat-masuk/{letter}/disposisi', [IncomingLetterController::class, 'storeDisposition'])->name('surat-masuk.disposisi.store');
        Route::patch('/disposisi/{disposition}/status', [IncomingLetterController::class, 'updateDispositionStatus'])->name('surat-masuk.disposisi.updateStatus');
    });
    // Catat & edit surat: hanya TU & Admin
    Route::middleware('role:Koordinator TU,Admin')->group(function () {
        Route::post('/surat-masuk', [IncomingLetterController::class, 'store'])->name('surat-masuk.store');
        Route::put('/surat-masuk/{letter}', [IncomingLetterController::class, 'update'])->name('surat-masuk.update');
    });

    // Penerima disposisi (Waka) melihat & memperbarui disposisi miliknya sendiri
    Route::middleware('role:' . implode(',', Disposition::ROLE_PENERIMA))->group(function () {
        Route::get('/disposisi-saya', [MyDispositionController::class, 'index'])->name('disposisi-saya.index');
        Route::patch('/disposisi-saya/{disposition}/status', [MyDispositionController::class, 'updateStatus'])->name('disposisi-saya.status');
    });

    // ---------- Fitur 3: Surat Keluar ----------
    Route::middleware('role:Koordinator TU,Admin,Kepala Sekolah')->group(function () {
        Route::get('/surat-keluar', [OutgoingLetterController::class, 'index'])->name('surat-keluar.index');
    });
    Route::middleware('role:Koordinator TU,Admin')->group(function () {
        Route::post('/surat-keluar', [OutgoingLetterController::class, 'store'])->name('surat-keluar.store');
        Route::put('/surat-keluar/{letter}', [OutgoingLetterController::class, 'update'])->name('surat-keluar.update');
    });
    Route::middleware('role:Kepala Sekolah')->group(function () {
        Route::post('/surat-keluar/{letter}/sign', [OutgoingLetterController::class, 'sign'])->name('surat-keluar.sign');
    });

    // ---------- Fitur 4: Mitra & Kerja Sama Industri ----------
    Route::middleware('role:Waka Humas,Kepala Program Keahlian,Kepala Sekolah,Admin')->group(function () {
        Route::get('/mitra-industri', [IndustryPartnerController::class, 'index'])->name('mitra-industri.index');
        Route::post('/mitra-industri/{approval}/decide', [IndustryPartnerController::class, 'decideApproval'])->name('mitra-industri.approval.decide');
    });
    Route::middleware('role:Waka Humas,Admin')->group(function () {
        Route::post('/mitra-industri', [IndustryPartnerController::class, 'store'])->name('mitra-industri.store');
        Route::post('/mitra-industri/{partner}/dokumen', [IndustryPartnerController::class, 'storeDocument'])->name('mitra-industri.dokumen.store');
    });

    // ---------- Fitur 5: Data Siswa PKL & Sertifikat ----------
    Route::middleware('role:Waka Humas,Admin')->group(function () {
        Route::get('/pkl', [PklPlacementController::class, 'index'])->name('pkl.index');
        Route::post('/pkl', [PklPlacementController::class, 'store'])->name('pkl.store');
        Route::post('/pkl/{placement}/selesai', [PklPlacementController::class, 'complete'])->name('pkl.complete');
        Route::post('/pkl/{placement}/batal', [PklPlacementController::class, 'cancel'])->name('pkl.cancel');

        Route::get('/sertifikat', [CertificateController::class, 'index'])->name('sertifikat.index');
        Route::post('/sertifikat', [CertificateController::class, 'store'])->name('sertifikat.store');
        Route::get('/sertifikat/{certificate}/unduh', [CertificateController::class, 'download'])->name('sertifikat.download');
    });
});

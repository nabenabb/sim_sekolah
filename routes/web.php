<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncomingLetterController;
use App\Http\Controllers\IndustryPartnerController;
use App\Http\Controllers\MyDispositionController;
use App\Http\Controllers\OutgoingLetterController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\PklPlacementController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LaporanTransferController;
use App\Models\Disposition;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');

// Halaman PUBLIK (tanpa login): dibuka saat QR di sertifikat di-scan
Route::get('/verifikasi/{token}', [CertificateController::class, 'verify'])->name('sertifikat.verify');

Route::get('/berkas/{path}', [PrivateFileController::class, 'show'])
    ->where('path', '.*')
    ->middleware(['auth', 'active'])
    ->name('private-file.show');

Route::middleware(['auth', 'active'])->group(function () {
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

    // ---------- Sampah & Pemulihan (khusus Admin) ----------
    Route::middleware('role:Admin')->group(function () {
        Route::get('/sampah', [TrashController::class, 'index'])->name('sampah.index');
        Route::post('/sampah/{jenis}/{id}/pulihkan', [TrashController::class, 'restore'])->whereNumber('id')->name('sampah.restore');
        Route::delete('/sampah/{jenis}/{id}/permanen', [TrashController::class, 'forceDelete'])->whereNumber('id')->name('sampah.force');
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
        Route::delete('/surat-masuk/{letter}', [IncomingLetterController::class, 'destroy'])->name('surat-masuk.destroy');
    });

    // Penerima disposisi (Waka) melihat & memperbarui disposisi miliknya sendiri
    Route::middleware('role:' . implode(',', Disposition::ROLE_PENERIMA))->group(function () {
        Route::get('/disposisi-saya', [MyDispositionController::class, 'index'])->name('disposisi-saya.index');
        Route::patch('/disposisi-saya/{disposition}/status', [MyDispositionController::class, 'updateStatus'])->name('disposisi-saya.status');
    });

    // ---------- Fitur 3: Surat Keluar ----------
    Route::middleware('role:Koordinator TU,Admin,Kepala Sekolah')->group(function () {
        Route::get('/surat-keluar', [OutgoingLetterController::class, 'index'])->name('surat-keluar.index');
        Route::get('/surat-keluar/{letter}/cetak', [OutgoingLetterController::class, 'downloadPdf'])->name('surat-keluar.pdf');
    });
    Route::middleware('role:Koordinator TU,Admin')->group(function () {
        Route::post('/surat-keluar', [OutgoingLetterController::class, 'store'])->name('surat-keluar.store');
        Route::put('/surat-keluar/{letter}', [OutgoingLetterController::class, 'update'])->name('surat-keluar.update');
        Route::delete('/surat-keluar/{letter}', [OutgoingLetterController::class, 'destroy'])->name('surat-keluar.destroy');
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
        Route::delete('/mitra-industri/{partner}', [IndustryPartnerController::class, 'destroy'])->name('mitra-industri.destroy');
        Route::delete('/mitra-industri/dokumen/{document}', [IndustryPartnerController::class, 'destroyDocument'])->name('mitra-industri.dokumen.destroy');
    });

    // ---------- Fitur 5: Data Siswa (master) + PKL & Sertifikat ----------
    Route::middleware('role:Waka Humas,Admin')->group(function () {
        Route::get('/siswa', [StudentController::class, 'index'])->name('siswa.index');
        Route::get('/siswa/template', [StudentController::class, 'template'])->name('siswa.template');
        Route::get('/siswa/ekspor', [StudentController::class, 'export'])->name('siswa.export');
        Route::post('/siswa/impor', [StudentController::class, 'import'])->middleware('throttle:10,1')->name('siswa.import');
        Route::post('/siswa', [StudentController::class, 'store'])->name('siswa.store');
        Route::put('/siswa/{siswa}', [StudentController::class, 'update'])->name('siswa.update');
        Route::delete('/siswa/{siswa}', [StudentController::class, 'destroy'])->name('siswa.destroy');

        Route::get('/pkl', [PklPlacementController::class, 'index'])->name('pkl.index');
        Route::post('/pkl', [PklPlacementController::class, 'store'])->name('pkl.store');
        Route::post('/pkl/{placement}/selesai', [PklPlacementController::class, 'complete'])->name('pkl.complete');
        Route::post('/pkl/{placement}/batal', [PklPlacementController::class, 'cancel'])->name('pkl.cancel');
        Route::delete('/pkl/{placement}', [PklPlacementController::class, 'destroy'])->name('pkl.destroy');

        Route::get('/sertifikat', [CertificateController::class, 'index'])->name('sertifikat.index');
        Route::post('/sertifikat', [CertificateController::class, 'store'])->name('sertifikat.store');
        Route::get('/sertifikat/{certificate}/unduh', [CertificateController::class, 'download'])->name('sertifikat.download');
        Route::delete('/sertifikat/{certificate}', [CertificateController::class, 'destroy'])->name('sertifikat.destroy');
    });

    // ---------- Fitur 6: Laporan ----------
    Route::middleware('role:Admin,Kepala Sekolah,Koordinator TU,Waka Humas')->group(function () {
        Route::get('/laporan/pratinjau/{jenis}', [LaporanTransferController::class, 'preview'])->name('laporan.preview');
        Route::get('/laporan/ekspor/{jenis}', [LaporanTransferController::class, 'export'])->name('laporan.export');
        Route::get('/laporan/ekspor-pdf/{jenis}', [LaporanTransferController::class, 'exportPdf'])->name('laporan.export.pdf');
        Route::get('/laporan/template/{jenis}', [LaporanTransferController::class, 'template'])->name('laporan.template');
        Route::post('/laporan/impor', [LaporanTransferController::class, 'import'])->middleware('throttle:10,1')->name('laporan.import');
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    });
});

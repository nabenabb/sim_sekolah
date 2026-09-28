<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\IncomingLetterController;
use App\Http\Controllers\IndustryPartnerController;
use App\Http\Controllers\OutgoingLetterController;
use App\Http\Controllers\PklPlacementController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// Halaman PUBLIK (tanpa login): dibuka saat QR di sertifikat di-scan
Route::get('/verifikasi/{token}', [CertificateController::class, 'verify'])->name('sertifikat.verify');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::get('/dashboard/tu', fn () => view('dashboard-tu'))->name('dashboard.tu');
    Route::get('/dashboard/kepsek', fn () => view('dashboard-kepsek'))->name('dashboard.kepsek');
    Route::get('/dashboard/humas', fn () => view('dashboard-humas'))->name('dashboard.humas');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ---------- Fitur 2: Surat Masuk & Disposisi (TU & Admin) ----------
    Route::middleware('role:Koordinator TU,Admin')->group(function () {
        Route::get('/surat-masuk', [IncomingLetterController::class, 'index'])->name('surat-masuk.index');
        Route::post('/surat-masuk', [IncomingLetterController::class, 'store'])->name('surat-masuk.store');
        Route::put('/surat-masuk/{letter}', [IncomingLetterController::class, 'update'])->name('surat-masuk.update');
        Route::post('/surat-masuk/{letter}/disposisi', [IncomingLetterController::class, 'storeDisposition'])->name('surat-masuk.disposisi.store');
        Route::patch('/disposisi/{disposition}/status', [IncomingLetterController::class, 'updateDispositionStatus'])->name('surat-masuk.disposisi.updateStatus');
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
    });

    // ---------- Fitur 5: Data Siswa PKL & Sertifikat (Waka Humas & Admin) ----------
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

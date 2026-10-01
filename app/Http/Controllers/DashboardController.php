<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\IndustryPartner;
use App\Models\OutgoingLetter;
use App\Models\PartnershipDocument;
use App\Models\PklPlacement;
use App\Models\Certificate;
use App\Models\User;

class DashboardController extends Controller
{
    // /dashboard: arahkan ke dashboard sesuai role. Role lain (mis. Admin) dapat halaman umum.
    public function index()
    {
        $user = auth()->user();
        $role = $user->role->name;

        return match ($role) {
            'Koordinator TU' => redirect()->route('dashboard.tu'),
            'Kepala Sekolah' => redirect()->route('dashboard.kepsek'),
            'Waka Humas' => redirect()->route('dashboard.humas'),
            default => view('dashboard', $this->genericData($user, $role)),
        };
    }

    // Isi dashboard umum, beda-beda tergantung role - dipakai role yang belum punya dashboard sendiri
    private function genericData($user, string $role): array
    {
        $data = ['role' => $role];

        if ($role === 'Admin') {
            $data['adminStats'] = [
                'userAktif' => User::where('is_active', true)->count(),
                'suratBulanIni' => IncomingLetter::whereYear('tanggal_terima', now()->year)->whereMonth('tanggal_terima', now()->month)->count(),
                'mitraAktif' => PartnershipDocument::where('status', 'disetujui')->whereDate('berlaku_selesai', '>=', now())->distinct()->count('partner_id'),
                'siswaPklAktif' => PklPlacement::where('status', 'berjalan')->count(),
                'sertifikatTerbit' => Certificate::count(),
            ];
        }

        if ($role === 'Kepala Program Keahlian') {
            $data['menungguKaprog'] = PartnershipDocument::with(['partner', 'approvals'])
                ->whereIn('status', ['diajukan', 'diverifikasi'])->get()
                ->filter(fn ($d) => optional($d->approvals->firstWhere('status', 'menunggu'))->tahap === 'Verifikasi Kaprog')
                ->values();
        }

        if ($role === 'Guru') {
            $data['bimbinganAktif'] = PklPlacement::with(['student.programKeahlian', 'partner'])
                ->where('pembimbing_id', $user->id)
                ->where('status', 'berjalan')
                ->get();
        }

        if (in_array($role, Disposition::ROLE_PENERIMA)) {
            $data['disposisiList'] = Disposition::with('letter')
                ->where('assigned_to', $user->id)
                ->where('status', '!=', 'selesai')
                ->latest()->take(5)->get();
        }

        return $data;
    }

    public function tu()
    {
        $now = now();
        $lalu = $now->copy()->subMonthNoOverflow();

        $masukIni = IncomingLetter::whereYear('tanggal_terima', $now->year)->whereMonth('tanggal_terima', $now->month)->count();
        $masukLalu = IncomingLetter::whereYear('tanggal_terima', $lalu->year)->whereMonth('tanggal_terima', $lalu->month)->count();
        $keluarIni = OutgoingLetter::whereYear('tanggal', $now->year)->whereMonth('tanggal', $now->month)->count();
        $keluarLalu = OutgoingLetter::whereYear('tanggal', $lalu->year)->whereMonth('tanggal', $lalu->month)->count();

        return view('dashboard-tu', [
            'masukIni' => $masukIni,
            'deltaMasuk' => $this->delta($masukIni, $masukLalu),
            'keluarIni' => $keluarIni,
            'deltaKeluar' => $this->delta($keluarIni, $keluarLalu),
            'disposisiTertunda' => Disposition::where('status', 'belum ditindaklanjuti')->count(),
            'suratTerbaru' => IncomingLetter::with('dispositions')->latest('tanggal_terima')->latest('id')->take(10)->get(),
            'perluTindak' => IncomingLetter::whereDoesntHave('dispositions')->latest('tanggal_terima')->take(5)->get(),
        ]);
    }

    public function kepsek()
    {
        $today = today();

        $menungguDisposisi = IncomingLetter::whereDoesntHave('dispositions')->latest('tanggal_terima')->get();
        $menungguTtd = OutgoingLetter::where('status', 'menunggu_ttd')->latest('tanggal')->get();

        // Kerja sama yang tahap aktifnya sudah "Persetujuan Kepala Sekolah"
        $menungguKepsek = PartnershipDocument::with(['partner', 'approvals'])
            ->whereIn('status', ['diajukan', 'diverifikasi'])
            ->get()
            ->filter(fn ($d) => optional($d->approvals->firstWhere('status', 'menunggu'))->tahap === 'Persetujuan Kepala Sekolah')
            ->values();

        $mouAktif = PartnershipDocument::where('status', 'disetujui')
            ->whereDate('berlaku_selesai', '>=', $today)
            ->distinct()->count('partner_id');

        $mouAkanBerakhir = PartnershipDocument::where('status', 'disetujui')
            ->whereDate('berlaku_selesai', '>=', $today)
            ->whereDate('berlaku_selesai', '<=', $today->copy()->addDays(30))
            ->distinct()->count('partner_id');

        $tindakan = collect();
        foreach ($menungguDisposisi->take(5) as $l) {
            $tindakan->push([
                'jenis' => 'Disposisi', 'pill' => 'pill-warning',
                'perihal' => $l->perihal, 'dari' => $l->pengirim,
                'label' => 'Buat disposisi →', 'url' => route('surat-masuk.index', ['surat' => $l->id]),
            ]);
        }
        foreach ($menungguKepsek->take(5) as $d) {
            $tindakan->push([
                'jenis' => 'Kerja Sama', 'pill' => 'pill-neutral',
                'perihal' => $d->jenis_dokumen . ' ' . $d->partner->nama_perusahaan, 'dari' => 'Waka Humas',
                'label' => 'Tinjau →', 'url' => route('mitra-industri.index'),
            ]);
        }
        foreach ($menungguTtd->take(5) as $s) {
            $tindakan->push([
                'jenis' => 'Surat Keluar', 'pill' => 'pill-neutral',
                'perihal' => $s->perihal, 'dari' => 'TU',
                'label' => 'Tanda tangani →', 'url' => route('surat-keluar.index'),
            ]);
        }

        return view('dashboard-kepsek', [
            'jumlahDisposisi' => $menungguDisposisi->count(),
            'jumlahTtd' => $menungguTtd->count(),
            'jumlahKerjaSama' => $menungguKepsek->count(),
            'mouAktif' => $mouAktif,
            'mouAkanBerakhir' => $mouAkanBerakhir,
            'tindakan' => $tindakan,
        ]);
    }

    public function humas()
    {
        $today = today();
        $now = now();

        $aktifQuery = PartnershipDocument::where('status', 'disetujui')->whereDate('berlaku_selesai', '>=', $today);

        $akanBerakhir = PartnershipDocument::with('partner')
            ->where('status', 'disetujui')
            ->whereDate('berlaku_selesai', '>=', $today)
            ->whereDate('berlaku_selesai', '<=', $today->copy()->addDays(30))
            ->orderBy('berlaku_selesai')
            ->get();

        $menungguVerifikasi = PartnershipDocument::with('approvals')
            ->whereIn('status', ['diajukan', 'diverifikasi'])
            ->get()
            ->filter(fn ($d) => optional($d->approvals->firstWhere('status', 'menunggu'))->tahap === 'Verifikasi Humas')
            ->count();

        $pklAktif = PklPlacement::where('status', 'berjalan');

        return view('dashboard-humas', [
            'today' => $today,
            'mitraAktif' => (clone $aktifQuery)->distinct()->count('partner_id'),
            'mitraBaru' => IndustryPartner::whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->count(),
            'akanBerakhir' => $akanBerakhir,
            'menungguVerifikasi' => $menungguVerifikasi,
            'siswaPkl' => (clone $pklAktif)->count(),
            'mitraPkl' => (clone $pklAktif)->distinct()->count('partner_id'),
            'disposisiSaya' => Disposition::where('assigned_to', auth()->id())->where('status', '!=', 'selesai')->count(),
        ]);
    }

    private function delta(int $ini, int $lalu): array
    {
        if ($lalu === 0) {
            return ['text' => 'Belum ada data bulan lalu', 'class' => 'up'];
        }

        $persen = (int) round(($ini - $lalu) / $lalu * 100);

        return $persen >= 0
            ? ['text' => "↑ {$persen}% dibanding bulan lalu", 'class' => 'up']
            : ['text' => '↓ ' . abs($persen) . '% dibanding bulan lalu', 'class' => 'warn'];
    }
}

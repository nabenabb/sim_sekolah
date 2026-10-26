<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\IncomingLetter;
use App\Models\IndustryPartner;
use App\Models\OutgoingLetter;
use App\Models\PartnershipDocument;
use App\Models\PklPlacement;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    /** Role yang boleh mengakses modul laporan */
    public const ALLOWED_ROLES = [
        'Admin',
        'Kepala Sekolah',
        'Koordinator TU',
        'Waka Humas',
    ];

    public function index(Request $request)
    {
        $role = auth()->user()->role->name;
        if (! in_array($role, self::ALLOWED_ROLES)) {
            abort(403, 'Akses ditolak');
        }

        $jenis = $request->query('jenis', 'ringkasan');
        $dari  = $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->query('sampai', now()->toDateString());

        // Validasi tanggal
        try {
            $dariDate = Carbon::parse($dari)->startOfDay();
            $sampaiDate = Carbon::parse($sampai)->endOfDay();
        } catch (\Exception $e) {
            $dariDate = now()->startOfMonth();
            $sampaiDate = now()->endOfDay();
            $dari = $dariDate->toDateString();
            $sampai = $sampaiDate->toDateString();
        }

        if ($dariDate->gt($sampaiDate)) {
            [$dariDate, $sampaiDate] = [$sampaiDate->copy()->startOfDay(), $dariDate->copy()->endOfDay()];
            $dari = $dariDate->toDateString();
            $sampai = $sampaiDate->toDateString();
        }

        $data = match ($jenis) {
            'surat-masuk'  => $this->laporanSuratMasuk($dariDate, $sampaiDate),
            'surat-keluar' => $this->laporanSuratKeluar($dariDate, $sampaiDate),
            'mitra'        => $this->laporanMitra($dariDate, $sampaiDate),
            'pkl'          => $this->laporanPkl($dariDate, $sampaiDate),
            'sertifikat'   => $this->laporanSertifikat($dariDate, $sampaiDate),
            default        => $this->laporanRingkasan($dariDate, $sampaiDate),
        };

        return view('laporan.index', array_merge($data, [
            'jenis'  => $jenis,
            'dari'   => $dari,
            'sampai' => $sampai,
            'role'   => $role,
        ]));
    }

    // ------------------------------------------------------------------
    // RINGKASAN
    // ------------------------------------------------------------------
    private function laporanRingkasan(Carbon $dari, Carbon $sampai): array
    {
        $suratMasuk = IncomingLetter::whereBetween('tanggal_terima', [$dari, $sampai])->count();
        $suratKeluar = OutgoingLetter::whereBetween('tanggal', [$dari, $sampai])->count();
        $mitraAktif = IndustryPartner::where('status', 'aktif')->count();
        $dokumenAktif = PartnershipDocument::where('status', 'disetujui')
            ->where('berlaku_selesai', '>=', now()->toDateString())
            ->count();
        $pklAktif = PklPlacement::where('status', 'berjalan')->count();
        $pklSelesai = PklPlacement::where('status', 'selesai')
            ->whereBetween('tanggal_selesai', [$dari, $sampai])
            ->count();
        $sertifikat = Certificate::whereBetween('tanggal_terbit', [$dari, $sampai])->count();

        // Breakdown surat keluar per status
        $keluarByStatus = OutgoingLetter::whereBetween('tanggal', [$dari, $sampai])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Breakdown PKL per status
        $pklByStatus = PklPlacement::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return compact(
            'suratMasuk', 'suratKeluar', 'mitraAktif', 'dokumenAktif',
            'pklAktif', 'pklSelesai', 'sertifikat',
            'keluarByStatus', 'pklByStatus'
        );
    }

    // ------------------------------------------------------------------
    // SURAT MASUK
    // ------------------------------------------------------------------
    private function laporanSuratMasuk(Carbon $dari, Carbon $sampai): array
    {
        $items = IncomingLetter::with(['dispositions', 'creator'])
            ->whereBetween('tanggal_terima', [$dari, $sampai])
            ->orderByDesc('tanggal_terima')
            ->get();

        $total = $items->count();
        $menunggu = $items->filter(fn ($l) => $l->dispositions->isEmpty())->count();
        $diproses = $items->filter(function ($l) {
            if ($l->dispositions->isEmpty()) return false;
            return ! $l->dispositions->every(fn ($d) => $d->status === 'selesai');
        })->count();
        $selesai = $items->filter(function ($l) {
            return $l->dispositions->isNotEmpty()
                && $l->dispositions->every(fn ($d) => $d->status === 'selesai');
        })->count();

        return compact('items', 'total', 'menunggu', 'diproses', 'selesai');
    }

    // ------------------------------------------------------------------
    // SURAT KELUAR
    // ------------------------------------------------------------------
    private function laporanSuratKeluar(Carbon $dari, Carbon $sampai): array
    {
        $items = OutgoingLetter::with(['creator', 'signer'])
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderByDesc('tanggal')
            ->get();

        $total = $items->count();
        $byStatus = $items->groupBy('status')->map->count();
        $byJenis  = $items->groupBy('jenis_template')->map->count();

        return compact('items', 'total', 'byStatus', 'byJenis');
    }

    // ------------------------------------------------------------------
    // MITRA / MoU-PKS
    // ------------------------------------------------------------------
    private function laporanMitra(Carbon $dari, Carbon $sampai): array
    {
        $partners = IndustryPartner::with(['programKeahlian', 'documents'])
            ->orderBy('nama_perusahaan')
            ->get();

        $documents = PartnershipDocument::with(['partner', 'approvals'])
            ->where(function ($q) use ($dari, $sampai) {
                $q->whereBetween('berlaku_mulai', [$dari, $sampai])
                  ->orWhereBetween('created_at', [$dari, $sampai]);
            })
            ->orderByDesc('berlaku_mulai')
            ->get();

        $totalMitra = $partners->count();
        $mitraAktif = $partners->where('status', 'aktif')->count();
        $dokumenByStatus = PartnershipDocument::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $akanKadaluarsa = PartnershipDocument::with('partner')
            ->where('status', 'disetujui')
            ->whereBetween('berlaku_selesai', [now()->toDateString(), now()->addDays(60)->toDateString()])
            ->orderBy('berlaku_selesai')
            ->get();

        return compact(
            'partners', 'documents', 'totalMitra', 'mitraAktif',
            'dokumenByStatus', 'akanKadaluarsa'
        );
    }

    // ------------------------------------------------------------------
    // PKL
    // ------------------------------------------------------------------
    private function laporanPkl(Carbon $dari, Carbon $sampai): array
    {
        $items = PklPlacement::with(['student.programKeahlian', 'partner', 'pembimbing'])
            ->where('tanggal_mulai', '<=', $sampai)
            ->where('tanggal_selesai', '>=', $dari)
            ->orderByDesc('tanggal_mulai')
            ->get();

        $total = $items->count();
        $byStatus = $items->groupBy('status')->map->count();

        // Per program keahlian
        $byJurusan = $items->groupBy(fn ($p) => $p->student->programKeahlian->nama_jurusan ?? '—')
            ->map->count()
            ->sortDesc();

        $siswaTotal = Student::count();

        return compact('items', 'total', 'byStatus', 'byJurusan', 'siswaTotal');
    }

    // ------------------------------------------------------------------
    // SERTIFIKAT
    // ------------------------------------------------------------------
    private function laporanSertifikat(Carbon $dari, Carbon $sampai): array
    {
        $items = Certificate::with(['placement.student.programKeahlian', 'placement.partner'])
            ->whereBetween('tanggal_terbit', [$dari, $sampai])
            ->orderByDesc('tanggal_terbit')
            ->get();

        $total = $items->count();

        return compact('items', 'total');
    }
}

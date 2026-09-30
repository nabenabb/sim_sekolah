<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\IndustryPartner;
use App\Models\OutgoingLetter;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    // Modul apa saja yang boleh dicari, per role - dijaga sama seperti middleware rute masing-masing
    private function suratRoles(): array
    {
        return ['Koordinator TU', 'Admin', 'Kepala Sekolah'];
    }

    private function mitraRoles(): array
    {
        return ['Waka Humas', 'Kepala Program Keahlian', 'Kepala Sekolah', 'Admin'];
    }

    private function pklRoles(): array
    {
        return ['Waka Humas', 'Admin'];
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $role = auth()->user()->role->name;
        $results = [];

        if ($q !== '') {
            if (in_array($role, $this->suratRoles())) {
                $results['Surat Masuk'] = IncomingLetter::where(function ($w) use ($q) {
                        $w->where('perihal', 'like', "%{$q}%")
                          ->orWhere('pengirim', 'like', "%{$q}%")
                          ->orWhere('no_agenda', 'like', "%{$q}%");
                    })
                    ->latest('tanggal_terima')->take(8)->get()
                    ->map(fn ($l) => [
                        'label' => $l->perihal,
                        'sub' => $l->pengirim . ' · ' . $l->no_agenda,
                        'url' => route('surat-masuk.index', ['surat' => $l->id]),
                    ]);

                $results['Surat Keluar'] = OutgoingLetter::where(function ($w) use ($q) {
                        $w->where('perihal', 'like', "%{$q}%")
                          ->orWhere('tujuan', 'like', "%{$q}%")
                          ->orWhere('no_surat', 'like', "%{$q}%");
                    })
                    ->latest('tanggal')->take(8)->get()
                    ->map(fn ($s) => [
                        'label' => $s->perihal,
                        'sub' => $s->tujuan . ' · ' . $s->no_surat,
                        'url' => route('surat-keluar.index', ['q' => $q]),
                    ]);
            }

            if (in_array($role, Disposition::ROLE_PENERIMA)) {
                $results['Disposisi Saya'] = Disposition::with('letter')
                    ->where('assigned_to', auth()->id())
                    ->whereHas('letter', function ($w) use ($q) {
                        $w->where('perihal', 'like', "%{$q}%")->orWhere('pengirim', 'like', "%{$q}%");
                    })
                    ->latest()->take(8)->get()
                    ->map(fn ($d) => [
                        'label' => $d->letter->perihal,
                        'sub' => 'Dari ' . $d->letter->pengirim . ' · ' . ucfirst($d->status),
                        'url' => route('disposisi-saya.index', ['q' => $q]),
                    ]);
            }

            if (in_array($role, $this->mitraRoles())) {
                $results['Mitra Industri'] = IndustryPartner::where(function ($w) use ($q) {
                        $w->where('nama_perusahaan', 'like', "%{$q}%")
                          ->orWhere('bidang_usaha', 'like', "%{$q}%")
                          ->orWhere('kontak_person', 'like', "%{$q}%");
                    })
                    ->take(8)->get()
                    ->map(fn ($p) => [
                        'label' => $p->nama_perusahaan,
                        'sub' => $p->bidang_usaha,
                        'url' => route('mitra-industri.index', ['q' => $q]),
                    ]);
            }

            if (in_array($role, $this->pklRoles())) {
                $results['Data Siswa PKL'] = Student::where(function ($w) use ($q) {
                        $w->where('nama', 'like', "%{$q}%")->orWhere('nis', 'like', "%{$q}%");
                    })
                    ->take(8)->get()
                    ->map(fn ($s) => [
                        'label' => $s->nama,
                        'sub' => 'NIS ' . $s->nis,
                        'url' => route('pkl.index', ['q' => $q]),
                    ]);
            }

            // Buang kelompok yang hasilnya kosong biar tampilan rapi
            $results = array_filter($results, fn ($group) => $group->isNotEmpty());
        }

        return view('cari.hasil', compact('q', 'results'));
    }
}

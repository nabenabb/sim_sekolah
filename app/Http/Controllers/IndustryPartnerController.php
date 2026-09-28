<?php

namespace App\Http\Controllers;

use App\Models\IndustryPartner;
use App\Models\PartnershipApproval;
use App\Models\PartnershipDocument;
use App\Models\ProgramKeahlian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndustryPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = IndustryPartner::with(['programKeahlian', 'latestActiveDocument', 'documents.approvals.approver']);

        if ($request->filled('jurusan')) {
            $query->where('program_keahlian_id', $request->query('jurusan'));
        }

        $partners = $query->get()->map(function ($partner) {
            $partner->computed_status = $this->computeStatus($partner);
            return $partner;
        });

        $statusFilter = $request->query('status', 'semua');
        if (in_array($statusFilter, ['aktif', 'akan_berakhir'])) {
            $partners = $partners->filter(fn ($p) => $p->computed_status['key'] === $statusFilter)->values();
        }

        $programKeahlian = ProgramKeahlian::all();

        $pengajuanBerjalan = PartnershipDocument::with(['partner', 'approvals'])
            ->whereIn('status', ['diajukan', 'diverifikasi'])
            ->latest()
            ->get();

        return view('mitra-industri.index', compact('partners', 'programKeahlian', 'statusFilter', 'pengajuanBerjalan'));
    }

    private function computeStatus(IndustryPartner $partner): array
    {
        $doc = $partner->latestActiveDocument;

        if (! $doc) {
            return ['key' => 'belum_aktif', 'label' => 'Belum Ada Kerja Sama', 'pill' => 'pill-neutral'];
        }

        $sisaHari = now()->diffInDays($doc->berlaku_selesai, false);

        if ($sisaHari < 0) {
            return ['key' => 'akan_berakhir', 'label' => 'Sudah Berakhir', 'pill' => 'pill-danger'];
        }

        if ($sisaHari <= 30) {
            return ['key' => 'akan_berakhir', 'label' => 'Akan Berakhir', 'pill' => 'pill-danger'];
        }

        return ['key' => 'aktif', 'label' => 'Aktif', 'pill' => 'pill-success'];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_perusahaan' => 'required|string|max:150',
            'alamat_pusat' => 'required|string|max:255',
            'bidang_usaha' => 'required|string|max:100',
            'program_keahlian_id' => 'required|exists:program_keahlian,id',
            'kontak_person' => 'required|string|max:100',
            'no_telepon' => 'nullable|string|max:30',
            'jenis_dokumen' => 'required|in:MoU,PKS,MoA',
            'ruang_lingkup' => 'nullable|string|max:255',
            'berlaku_mulai' => 'required|date',
            'berlaku_selesai' => 'required|date|after:berlaku_mulai',
            'dokumen' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        DB::transaction(function () use ($data, $request) {
            $partner = IndustryPartner::create([
                'nama_perusahaan' => $data['nama_perusahaan'],
                'alamat_pusat' => $data['alamat_pusat'],
                'bidang_usaha' => $data['bidang_usaha'],
                'kontak_person' => $data['kontak_person'],
                'no_telepon' => $data['no_telepon'] ?? null,
                'status' => 'aktif',
                'program_keahlian_id' => $data['program_keahlian_id'],
            ]);

            $filePath = $request->hasFile('dokumen')
                ? $request->file('dokumen')->store('kerja-sama', 'public')
                : null;

            $document = PartnershipDocument::create([
                'partner_id' => $partner->id,
                'jenis_dokumen' => $data['jenis_dokumen'],
                'ruang_lingkup' => $data['ruang_lingkup'] ?? null,
                'berlaku_mulai' => $data['berlaku_mulai'],
                'berlaku_selesai' => $data['berlaku_selesai'],
                'file_dokumen' => $filePath,
                'status' => 'diajukan',
            ]);

            foreach (array_keys(PartnershipApproval::TAHAP_ROLE) as $tahap) {
                PartnershipApproval::create([
                    'document_id' => $document->id,
                    'tahap' => $tahap,
                    'status' => 'menunggu',
                ]);
            }
        });

        return redirect()->route('mitra-industri.index')->with('success', 'Mitra & pengajuan kerja sama berhasil dikirim untuk verifikasi.');
    }

    public function decideApproval(Request $request, PartnershipApproval $approval)
    {
        $request->validate(['keputusan' => 'required|in:disetujui,ditolak']);

        $roleYangBoleh = PartnershipApproval::TAHAP_ROLE[$approval->tahap] ?? null;

        if (auth()->user()->role->name !== $roleYangBoleh) {
            abort(403, 'Anda tidak berwenang memutuskan tahap ini.');
        }

        $approval->update([
            'status' => $request->keputusan,
            'approved_by' => auth()->id(),
            'tanggal' => now(),
        ]);

        $document = $approval->document;

        if ($request->keputusan === 'ditolak') {
            $document->update(['status' => 'ditolak']);
        } else {
            $semuaTahap = $document->approvals()->get();
            $semuaDisetujui = $semuaTahap->every(fn ($a) => $a->status === 'disetujui');
            $adaYangSelesai = $semuaTahap->where('status', 'disetujui')->count() > 0;

            $document->update([
                'status' => $semuaDisetujui ? 'disetujui' : ($adaYangSelesai ? 'diverifikasi' : 'diajukan'),
            ]);
        }

        return redirect()->route('mitra-industri.index')->with('success', 'Keputusan tahap "'.$approval->tahap.'" berhasil disimpan.');
    }
}

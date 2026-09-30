<?php

namespace App\Http\Controllers;

use App\Models\IndustryPartner;
use App\Models\PklPlacement;
use App\Models\ProgramKeahlian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class PklPlacementController extends Controller
{
    public function index(Request $request)
    {
        $query = PklPlacement::with(['student.programKeahlian', 'partner', 'pembimbing', 'certificate']);

        $status = $request->query('status', 'semua');
        if (in_array($status, ['berjalan', 'selesai'])) {
            $query->where('status', $status);
        }

        if ($request->filled('q')) {
            $keyword = $request->query('q');
            $query->where(function ($w) use ($keyword) {
                $w->whereHas('student', fn ($s) => $s->where('nama', 'like', "%{$keyword}%")->orWhere('nis', 'like', "%{$keyword}%"))
                  ->orWhereHas('partner', fn ($pt) => $pt->where('nama_perusahaan', 'like', "%{$keyword}%"));
            });
        }

        if ($request->filled('jurusan')) {
            $query->whereHas('student', fn ($q) => $q->where('program_keahlian_id', $request->query('jurusan')));
        }

        $placements = $query->latest('tanggal_mulai')->paginate(10)->withQueryString();

        $jumlahAktif = PklPlacement::where('status', 'berjalan')->count();

        // Data buat dropdown di form "Input Penempatan"
        $students = Student::with('programKeahlian')
            ->whereDoesntHave('placements', fn ($q) => $q->where('status', 'berjalan'))
            ->orderBy('nama')
            ->get();
        $partners = IndustryPartner::orderBy('nama_perusahaan')->get();
        $pembimbingList = User::whereHas('role', fn ($q) => $q->whereIn('name', ['Guru', 'Kepala Program Keahlian']))
            ->orderBy('name')
            ->get();
        $programKeahlian = ProgramKeahlian::all();

        return view('pkl.index', compact(
            'placements', 'status', 'jumlahAktif', 'students', 'partners', 'pembimbingList', 'programKeahlian'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'partner_id' => 'required|exists:industry_partners,id',
            'pembimbing_id' => 'required|exists:users,id',
            'pembimbing_industri' => 'nullable|string|max:100',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'surat_permohonan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // 1 siswa hanya boleh punya 1 penempatan yang sedang berjalan
        $sudahAda = PklPlacement::where('student_id', $data['student_id'])->where('status', 'berjalan')->exists();
        if ($sudahAda) {
            return back()->withErrors(['student_id' => 'Siswa ini masih punya penempatan PKL yang berjalan.'])->withInput();
        }

        if ($request->hasFile('surat_permohonan')) {
            $data['surat_permohonan'] = $request->file('surat_permohonan')->store('pkl', 'public');
        }

        $data['status'] = 'berjalan';

        PklPlacement::create($data);

        return redirect()->route('pkl.index')->with('success', 'Penempatan PKL berhasil disimpan.');
    }

    // Tandai selesai + isi nilai akhir
    public function complete(Request $request, PklPlacement $placement)
    {
        if ($placement->status !== 'berjalan') {
            abort(403, 'Hanya penempatan yang berjalan yang bisa diselesaikan.');
        }

        $data = $request->validate([
            'nilai' => 'required|numeric|min:0|max:100',
        ]);

        $placement->update(['status' => 'selesai', 'nilai' => $data['nilai']]);

        return redirect()->route('pkl.index')->with('success', 'PKL ditandai selesai. Sertifikat sekarang bisa diterbitkan.');
    }

    public function cancel(PklPlacement $placement)
    {
        if ($placement->status !== 'berjalan') {
            abort(403, 'Hanya penempatan yang berjalan yang bisa dibatalkan.');
        }

        $placement->update(['status' => 'dibatalkan']);

        return redirect()->route('pkl.index')->with('success', 'Penempatan PKL dibatalkan.');
    }
}

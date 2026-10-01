<?php

namespace App\Http\Controllers;

use App\Models\ProgramKeahlian;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('programKeahlian')->orderBy('nama');

        if ($request->filled('q')) {
            $keyword = $request->query('q');
            $query->where(function ($w) use ($keyword) {
                $w->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('nis', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('jurusan')) {
            $query->where('program_keahlian_id', $request->query('jurusan'));
        }

        $students = $query->paginate(15)->withQueryString();
        $programKeahlian = ProgramKeahlian::orderBy('nama_jurusan')->get();

        $editing = $request->filled('edit')
            ? Student::find($request->query('edit'))
            : null;

        return view('siswa.index', compact('students', 'programKeahlian', 'editing'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100',
            'nis' => 'required|string|max:20|unique:students,nis',
            'tanggal_lahir' => 'nullable|date',
            'program_keahlian_id' => 'required|exists:program_keahlian,id',
        ]);

        Student::create($data);

        return redirect()->route('siswa.index')
            ->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Student $siswa)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100',
            'nis' => [
                'required',
                'string',
                'max:20',
                Rule::unique('students', 'nis')->ignore($siswa->id),
            ],
            'tanggal_lahir' => 'nullable|date',
            'program_keahlian_id' => 'required|exists:program_keahlian,id',
        ]);

        $siswa->update($data);

        return redirect()->route('siswa.index')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $siswa)
    {
        // Cegah hapus jika masih punya penempatan aktif
        $masihAktif = $siswa->placements()->where('status', 'berjalan')->exists();
        if ($masihAktif) {
            return back()->withErrors([
                'siswa' => 'Siswa masih memiliki penempatan PKL yang berjalan. Selesaikan/batalkan dulu.',
            ]);
        }

        $siswa->delete(); // soft delete

        return redirect()->route('siswa.index')
            ->with('success', 'Siswa berhasil dihapus dari daftar.');
    }
}

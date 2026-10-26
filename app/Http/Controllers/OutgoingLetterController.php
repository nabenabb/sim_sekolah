<?php

namespace App\Http\Controllers;

use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Student;
use App\Models\User;
use App\Support\DocumentNumberGenerator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OutgoingLetterController extends Controller
{
    public function index(Request $request)
    {
        $query = OutgoingLetter::with(['refLetter', 'signer'])->latest('tanggal');

        if ($request->filled('q')) {
            $keyword = $request->query('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('perihal', 'like', "%{$keyword}%")
                  ->orWhere('tujuan', 'like', "%{$keyword}%")
                  ->orWhere('no_surat', 'like', "%{$keyword}%");
            });
        }

        $status = $request->query('status', 'semua');
        if ($status === 'arsip') {
            // "Arsip" = surat yang sudah terkirim, prosesnya final
            $query->where('status', 'terkirim');
        } elseif (in_array($status, ['draf', 'menunggu_ttd', 'terkirim'])) {
            $query->where('status', $status);
        }

        $letters = $query->paginate(10)->withQueryString();
        $incomingLetters = IncomingLetter::orderByDesc('tanggal_terima')->get();
        $students = Student::orderBy('nama')->get();

        $editing = $request->filled('edit')
            ? OutgoingLetter::where('status', 'draf')->findOrFail($request->query('edit'))
            : null;

        return view('surat-keluar.index', compact('letters', 'incomingLetters', 'students', 'status', 'editing'));
    }

    public function store(Request $request)
    {
        $jenis = $request->input('jenis_template', 'umum');
        $data = $this->validatedData($request, $jenis);

        // Nomor urut = nomor terakhir tahun ini TERMASUK surat di Sampah (soft delete),
        // supaya nomor tidak pernah dobel dengan surat lain (no_surat bersifat unik).
        $data['no_surat'] = app(DocumentNumberGenerator::class)->outgoingLetter();
        $data['created_by'] = auth()->id();
        $data['jenis_template'] = $jenis;

        if ($jenis === 'umum') {
            $data['status'] = $request->has('ajukan') ? 'menunggu_ttd' : 'draf';
            if ($request->hasFile('lampiran')) {
                $data['file_path'] = $request->file('lampiran')->store('surat-keluar', 'local');
            }
            $letter = OutgoingLetter::create($data);
        } elseif ($jenis === 'undangan') {
            $data['status'] = $request->has('ajukan') ? 'menunggu_ttd' : 'draf';
            $letter = OutgoingLetter::create($data);
            $this->generateDanSimpanPdf($letter);
        } else { // izin_siswa - dicetak langsung di tempat, tidak lewat alur draf/TTD
            $data['status'] = 'terkirim';
            $data['signed_by'] = auth()->id();
            $letter = OutgoingLetter::create($data);
            $this->generateDanSimpanPdf($letter);
        }

        $pesan = match (true) {
            $jenis === 'izin_siswa' => 'Surat izin siswa berhasil dibuat, langsung bisa dicetak.',
            $data['status'] === 'menunggu_ttd' => 'Surat diajukan, menunggu tanda tangan Kepala Sekolah.',
            default => 'Draf surat keluar disimpan.',
        };

        return redirect()->route('surat-keluar.index', ['surat' => $letter->id])->with('success', $pesan);
    }

    public function update(Request $request, OutgoingLetter $letter)
    {
        if ($letter->status !== 'draf') {
            abort(403, 'Surat yang sudah diajukan tidak bisa diedit lagi.');
        }

        $jenis = $letter->jenis_template; // jenis template tidak bisa diganti saat edit
        $data = $this->validatedData($request, $jenis);
        $data['status'] = $request->has('ajukan') ? 'menunggu_ttd' : 'draf';

        if ($jenis === 'umum' && $request->hasFile('lampiran')) {
            $oldPath = $letter->file_path;
            $data['file_path'] = $request->file('lampiran')->store('surat-keluar', 'local');
            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
                Storage::disk('public')->delete($oldPath);
            }
        }

        $letter->update($data);

        if ($jenis !== 'umum') {
            $this->generateDanSimpanPdf($letter->fresh());
        }

        return redirect()->route('surat-keluar.index')->with('success', 'Surat keluar berhasil diperbarui.');
    }

    private function validatedData(Request $request, string $jenis): array
    {
        $base = $request->validate([
            'ref_incoming_letter_id' => 'nullable|exists:incoming_letters,id',
            'tanggal' => 'required|date',
        ]);

        if ($jenis === 'umum') {
            return $base + $request->validate([
                'tujuan' => 'required|string|max:150',
                'perihal' => 'required|string|max:255',
                'isi_ringkas' => 'nullable|string',
                'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);
        }

        if ($jenis === 'undangan') {
            $v = $request->validate([
                'tujuan' => 'required|string|max:150',
                'perihal' => 'required|string|max:255',
                'sifat' => 'required|in:Biasa,Segera,Sangat Segera,Rahasia',
                'lampiran_keterangan' => 'nullable|string|max:100',
                'lokasi_tujuan' => 'nullable|string|max:100',
                'pembuka' => 'required|string',
                'hari_tanggal' => 'required|string|max:100',
                'pukul' => 'required|string|max:50',
                'tempat' => 'required|string|max:150',
                'penutup' => 'nullable|string',
            ]);

            return $base + [
                'tujuan' => $v['tujuan'],
                'perihal' => $v['perihal'],
                'isi_ringkas' => $v['pembuka'],
                'data_template' => [
                    'sifat' => $v['sifat'],
                    'lampiran' => $v['lampiran_keterangan'] ?? '-',
                    'lokasi_tujuan' => $v['lokasi_tujuan'] ?? 'Tanjungpinang',
                    'pembuka' => $v['pembuka'],
                    'hari_tanggal' => $v['hari_tanggal'],
                    'pukul' => $v['pukul'],
                    'tempat' => $v['tempat'],
                    'penutup' => $v['penutup'] ?? 'Atas perhatian dan kehadiran Bapak/Ibu diucapkan terima kasih.',
                ],
            ];
        }

        // izin_siswa
        $v = $request->validate([
            'student_id' => 'required|exists:students,id',
            'kelas' => 'required|string|max:30',
            'tujuan_izin' => 'required|string|max:150',
            'jam_keluar' => 'required|string|max:20',
            'jam_kembali' => 'required|string|max:20',
        ]);

        $student = Student::findOrFail($v['student_id']);

        return $base + [
            'tujuan' => $student->nama,
            'perihal' => 'Surat Izin Siswa - ' . $student->nama,
            'isi_ringkas' => $v['tujuan_izin'],
            'data_template' => [
                'nama_siswa' => $student->nama,
                'nis' => $student->nis,
                'kelas' => $v['kelas'],
                'tujuan_izin' => $v['tujuan_izin'],
                'jam_keluar' => $v['jam_keluar'],
                'jam_kembali' => $v['jam_kembali'],
            ],
        ];
    }

    // Kepala Sekolah menandatangani surat (khusus jenis umum & undangan)
    public function sign(OutgoingLetter $letter)
    {
        if ($letter->status !== 'menunggu_ttd') {
            abort(403, 'Surat ini tidak sedang menunggu tanda tangan.');
        }

        $letter->update([
            'status' => 'terkirim',
            'signed_by' => auth()->id(),
        ]);

        return redirect()->route('surat-keluar.index')->with('success', 'Surat berhasil ditandatangani & dikirim.');
    }

    private function generateDanSimpanPdf(OutgoingLetter $letter): void
    {
        $pdf = $this->renderPdf($letter);
        $path = 'surat-keluar/' . str_replace('/', '-', $letter->no_surat) . '.pdf';
        Storage::disk('local')->put($path, $pdf);

        $letter->update(['file_path' => $path]);
    }

    public function downloadPdf(OutgoingLetter $letter): StreamedResponse
    {
        $letter->loadMissing('signer');
        $pdf = $this->renderPdf($letter);
        $filename = str_replace('/', '-', $letter->no_surat) . '.pdf';

        return response()->streamDownload(static function () use ($pdf): void {
            echo $pdf;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    private function renderPdf(OutgoingLetter $letter): string
    {
        $kepsek = User::whereHas('role', fn ($q) => $q->where('name', 'Kepala Sekolah'))->first();

        $view = match ($letter->jenis_template) {
            'umum' => 'surat-keluar.pdf-umum',
            'undangan' => 'surat-keluar.pdf-undangan',
            default => 'surat-keluar.pdf-izin-siswa',
        };

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, [
            'surat' => $letter,
            'data' => $letter->data_template ?? [],
            'namaKepsek' => $letter->signer?->name ?? $kepsek->name ?? '______________________',
            'logoProvinsi' => $this->logoDataUri('logo-provinsi-kepulauan-riau.png'),
            'logoSekolah' => $this->logoDataUri('logo-smkn4-tanjungpinang.png'),
        ])->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function logoDataUri(string $filename): string
    {
        return 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/' . $filename)));
    }

    public function destroy(OutgoingLetter $letter)
    {
        if (! in_array(auth()->user()->role->name, ['Admin', 'Koordinator TU'])) {
            abort(403, 'Akses ditolak');
        }

        $letter->delete();

        return redirect()->route('surat-keluar.index')
            ->with('success', 'Surat keluar berhasil dihapus dari daftar.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Illuminate\Http\Request;

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
        if (in_array($status, ['draf', 'menunggu_ttd', 'terkirim'])) {
            $query->where('status', $status);
        }

        $letters = $query->paginate(10)->withQueryString();

        // Daftar surat masuk buat dropdown "Berdasarkan Surat Masuk"
        $incomingLetters = IncomingLetter::orderByDesc('tanggal_terima')->get();

        $editing = $request->filled('edit')
            ? OutgoingLetter::where('status', 'draf')->findOrFail($request->query('edit'))
            : null;

        return view('surat-keluar.index', compact('letters', 'incomingLetters', 'status', 'editing'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        // 2 tombol submit beda nama: 'simpan_draf' atau 'ajukan'
        $data['status'] = $request->has('ajukan') ? 'menunggu_ttd' : 'draf';

        if ($request->hasFile('lampiran')) {
            $data['file_path'] = $request->file('lampiran')->store('surat-keluar', 'public');
        }

        $urutan = OutgoingLetter::whereYear('tanggal', now()->year)->count() + 1;
        $data['no_surat'] = sprintf('%03d/SMKN4/%s/%s', $urutan, now()->format('m'), now()->format('y'));
        $data['created_by'] = auth()->id();

        OutgoingLetter::create($data);

        $pesan = $data['status'] === 'menunggu_ttd'
            ? 'Surat diajukan, menunggu tanda tangan Kepala Sekolah.'
            : 'Draf surat keluar disimpan.';

        return redirect()->route('surat-keluar.index')->with('success', $pesan);
    }

    public function update(Request $request, OutgoingLetter $letter)
    {
        // Surat yang statusnya udah bukan draf (menunggu TTD/terkirim) dikunci, nggak boleh diedit lagi
        if ($letter->status !== 'draf') {
            abort(403, 'Surat yang sudah diajukan tidak bisa diedit lagi.');
        }

        $data = $this->validatedData($request);
        $data['status'] = $request->has('ajukan') ? 'menunggu_ttd' : 'draf';

        if ($request->hasFile('lampiran')) {
            $data['file_path'] = $request->file('lampiran')->store('surat-keluar', 'public');
        }

        $letter->update($data);

        return redirect()->route('surat-keluar.index')->with('success', 'Surat keluar berhasil diperbarui.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'ref_incoming_letter_id' => 'nullable|exists:incoming_letters,id',
            'tanggal' => 'required|date',
            'tujuan' => 'required|string|max:150',
            'perihal' => 'required|string|max:255',
            'isi_ringkas' => 'nullable|string',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
    }

    // Kepala Sekolah menandatangani surat yang berstatus "menunggu_ttd"
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
}

<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncomingLetterController extends Controller
{
    public function index(Request $request)
    {
        $query = IncomingLetter::with('dispositions')->latest('tanggal_terima');

        // ---- Search (perihal / pengirim / no. agenda) ----
        if ($request->filled('q')) {
            $keyword = $request->query('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('perihal', 'like', "%{$keyword}%")
                  ->orWhere('pengirim', 'like', "%{$keyword}%")
                  ->orWhere('no_agenda', 'like', "%{$keyword}%");
            });
        }

        // ---- Filter chip: semua / menunggu / ditindaklanjuti ----
        $status = $request->query('status', 'semua');
        if ($status === 'menunggu') {
            $query->whereDoesntHave('dispositions');
        } elseif ($status === 'ditindaklanjuti') {
            $query->whereHas('dispositions');
        }

        $letters = $query->paginate(8)->withQueryString();

        $selected = $request->filled('surat')
            ? IncomingLetter::with(['dispositions.assigned'])->findOrFail($request->query('surat'))
            : $letters->first();

        return view('surat-masuk.index', compact('letters', 'selected', 'status'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('file_scan')) {
            $data['file_path'] = $request->file('file_scan')->store('surat-masuk', 'public');
        }

        $urutan = IncomingLetter::whereYear('tanggal_terima', now()->year)->count() + 1;
        $data['no_agenda'] = sprintf('%03d/AGD/%s/%s', $urutan, now()->format('m'), now()->format('y'));
        $data['created_by'] = auth()->id();

        $letter = IncomingLetter::create($data);

        return redirect()->route('surat-masuk.index', ['surat' => $letter->id])
            ->with('success', 'Surat masuk berhasil dicatat.');
    }

    public function update(Request $request, IncomingLetter $letter)
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('file_scan')) {
            $data['file_path'] = $request->file('file_scan')->store('surat-masuk', 'public');
        }

        $letter->update($data);

        return redirect()->route('surat-masuk.index', ['surat' => $letter->id])
            ->with('success', 'Surat masuk berhasil diperbarui.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'pengirim' => 'required|string|max:150',
            'nomor_surat' => 'nullable|string|max:50',
            'tanggal_surat' => 'nullable|date',
            'tanggal_terima' => 'required|date',
            'sifat' => 'required|in:Biasa,Segera,Sangat Segera,Rahasia',
            'perihal' => 'required|string|max:255',
            'file_scan' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
    }

    public function storeDisposition(Request $request, IncomingLetter $letter)
    {
        $validated = $request->validate([
            'tujuan' => 'required|array|min:1',
            'instruksi' => 'required|array|min:1',
            'catatan' => 'nullable|string',
        ]);

        $instruksiText = implode(', ', $validated['instruksi']);
        if (! empty($validated['catatan'])) {
            $instruksiText .= ' — ' . $validated['catatan'];
        }

        $terkirim = [];
        $dilewati = [];

        foreach ($validated['tujuan'] as $namaRole) {
            $user = User::whereHas('role', fn ($q) => $q->where('name', $namaRole))->first();

            if ($user) {
                Disposition::create([
                    'letter_id' => $letter->id,
                    'instruksi' => $instruksiText,
                    'status' => 'belum ditindaklanjuti',
                    'assigned_to' => $user->id,
                ]);
                $terkirim[] = $namaRole;
            } else {
                $dilewati[] = $namaRole;
            }
        }

        if (empty($terkirim)) {
            return back()->withErrors(['tujuan' => 'Belum ada user dengan role ' . implode(', ', $dilewati) . '. Buat user untuk role tersebut dulu.']);
        }

        $pesan = 'Disposisi berhasil dikirim ke: ' . implode(', ', $terkirim) . '.';
        if (! empty($dilewati)) {
            $pesan .= ' Dilewati karena belum ada user dengan role ini: ' . implode(', ', $dilewati) . '.';
        }

        return redirect()->route('surat-masuk.index', ['surat' => $letter->id])
            ->with('success', $pesan);
    }

    public function updateDispositionStatus(Request $request, Disposition $disposition)
    {
        $request->validate([
            'status' => 'required|in:belum ditindaklanjuti,diproses,selesai',
        ]);

        $disposition->update(['status' => $request->status]);

        return redirect()->route('surat-masuk.index', ['surat' => $disposition->letter_id])
            ->with('success', 'Status disposisi diperbarui.');
    }

    public function destroy(IncomingLetter $letter)
    {
        if (! in_array(auth()->user()->role->name, ['Admin', 'Koordinator TU'])) {
            abort(403, 'Akses ditolak');
        }

        // Soft delete saja — disposisi & file tetap di DB/storage (opsi A)
        $letter->delete();

        return redirect()->route('surat-masuk.index')
            ->with('success', 'Surat masuk berhasil dihapus dari daftar.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use Illuminate\Http\Request;

class MyDispositionController extends Controller
{
    private const FILTER = [
        'belum' => 'belum ditindaklanjuti',
        'diproses' => 'diproses',
        'selesai' => 'selesai',
    ];

    public function index(Request $request)
    {
        $status = $request->query('status', 'semua');

        $query = Disposition::with('letter')->where('assigned_to', auth()->id());

        if (isset(self::FILTER[$status])) {
            $query->where('status', self::FILTER[$status]);
        }

        if ($request->filled('q')) {
            $keyword = $request->query('q');
            $query->whereHas('letter', function ($w) use ($keyword) {
                $w->where('perihal', 'like', "%{$keyword}%")->orWhere('pengirim', 'like', "%{$keyword}%");
            });
        }

        $dispositions = $query->latest()->paginate(10)->withQueryString();

        return view('disposisi-saya.index', compact('dispositions', 'status'));
    }

    // Penerima hanya boleh mengubah status disposisi miliknya sendiri
    public function updateStatus(Request $request, Disposition $disposition)
    {
        abort_unless((int) $disposition->assigned_to === (int) auth()->id(), 403, 'Disposisi ini bukan untuk Anda.');

        $request->validate([
            'status' => 'required|in:belum ditindaklanjuti,diproses,selesai',
        ]);

        $disposition->update(['status' => $request->status]);

        return back()->with('success', 'Status disposisi diperbarui.');
    }
}

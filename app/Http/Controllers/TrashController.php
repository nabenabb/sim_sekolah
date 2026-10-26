<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\IndustryPartner;
use App\Models\OutgoingLetter;
use App\Models\PartnershipApproval;
use App\Models\PartnershipDocument;
use App\Models\PklPlacement;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Halaman "Sampah" khusus Admin: melihat data yang sudah dihapus (soft delete),
 * memulihkannya, atau menghapusnya permanen.
 */
class TrashController extends Controller
{
    // Satu tempat untuk semua jenis data yang bisa dipulihkan.
    private function config(): array
    {
        return [
            'surat-masuk' => [
                'label' => 'Surat Masuk', 'model' => IncomingLetter::class, 'with' => [],
                'title' => fn ($m) => $m->perihal,
                'sub' => fn ($m) => $m->no_agenda . ' · ' . $m->pengirim,
            ],
            'surat-keluar' => [
                'label' => 'Surat Keluar', 'model' => OutgoingLetter::class, 'with' => [],
                'title' => fn ($m) => $m->perihal,
                'sub' => fn ($m) => $m->no_surat . ' · ' . $m->tujuan,
            ],
            'mitra' => [
                'label' => 'Mitra Industri', 'model' => IndustryPartner::class, 'with' => [],
                'title' => fn ($m) => $m->nama_perusahaan,
                'sub' => fn ($m) => $m->bidang_usaha,
            ],
            'dokumen' => [
                'label' => 'Dokumen Kerja Sama', 'model' => PartnershipDocument::class, 'with' => ['partner'],
                'title' => fn ($m) => $m->jenis_dokumen . ' · ' . ($m->partner->nama_perusahaan ?? '—'),
                'sub' => fn ($m) => ($m->nomor_dokumen ?: 'tanpa nomor') . ' · berlaku ' . $m->berlaku_mulai->format('d/m/Y') . ' – ' . $m->berlaku_selesai->format('d/m/Y'),
            ],
            'siswa' => [
                'label' => 'Siswa', 'model' => Student::class, 'with' => [],
                'title' => fn ($m) => $m->nama,
                'sub' => fn ($m) => 'NIS ' . $m->nis,
            ],
            'pkl' => [
                'label' => 'Penempatan PKL', 'model' => PklPlacement::class, 'with' => ['student', 'partner'],
                'title' => fn ($m) => ($m->student->nama ?? '—'),
                'sub' => fn ($m) => ($m->partner->nama_perusahaan ?? '—') . ' · ' . $m->periodeText(),
            ],
            'sertifikat' => [
                'label' => 'Sertifikat', 'model' => Certificate::class, 'with' => ['placement.student'],
                'title' => fn ($m) => $m->nomor_sertifikat,
                'sub' => fn ($m) => ($m->placement->student->nama ?? '—'),
            ],
        ];
    }

    private function resolve(string $jenis): array
    {
        $cfg = $this->config();
        abort_unless(isset($cfg[$jenis]), 404);

        return $cfg[$jenis];
    }

    public function index(Request $request)
    {
        $jenis = $request->query('jenis', 'surat-masuk');
        $config = $this->resolve($jenis);

        $counts = collect($this->config())->map(fn ($c) => $c['model']::onlyTrashed()->count());

        $items = $config['model']::onlyTrashed()
            ->with($config['with'])
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        $tabs = collect($this->config())->map(fn ($c) => $c['label']);

        return view('sampah.index', compact('jenis', 'config', 'items', 'counts', 'tabs'));
    }

    public function restore(string $jenis, int $id)
    {
        $config = $this->resolve($jenis);
        $item = $config['model']::onlyTrashed()->findOrFail($id);
        $item->restore();

        return redirect()->route('sampah.index', ['jenis' => $jenis])
            ->with('success', $config['label'] . ' berhasil dipulihkan.');
    }

    public function forceDelete(string $jenis, int $id)
    {
        $config = $this->resolve($jenis);
        $item = $config['model']::onlyTrashed()->findOrFail($id);

        $files = [];

        try {
            DB::transaction(function () use ($jenis, $item, &$files) {
                $files = $this->hapusPermanen($jenis, $item);
            });
        } catch (RuntimeException $e) {
            // Penolakan yang disengaja (data masih dipakai) - pesannya aman ditampilkan
            return back()->withErrors(['sampah' => $e->getMessage()]);
        } catch (QueryException $e) {
            return back()->withErrors(['sampah' => 'Tidak bisa dihapus permanen karena masih dipakai data lain.']);
        }

        // File fisik baru dihapus setelah database sukses, supaya tidak ada file hilang tapi data masih ada
        foreach (array_filter($files) as $path) {
            Storage::disk('local')->delete($path);
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('sampah.index', ['jenis' => $jenis])
            ->with('success', $config['label'] . ' dihapus permanen.');
    }

    // Menghapus permanen satu data + turunan yang memang ikut (disposisi, persetujuan).
    // Mengembalikan daftar file yang harus dihapus dari storage.
    private function hapusPermanen(string $jenis, $item): array
    {
        $files = [];

        switch ($jenis) {
            case 'surat-masuk':
                Disposition::withTrashed()->where('letter_id', $item->id)->get()->each->forceDelete();
                // Surat keluar yang merujuk surat ini tidak ikut dihapus, cukup dilepas rujukannya
                OutgoingLetter::withTrashed()->where('ref_incoming_letter_id', $item->id)->update(['ref_incoming_letter_id' => null]);
                $files[] = $item->file_path;
                break;

            case 'surat-keluar':
                $files[] = $item->file_path;
                break;

            case 'mitra':
                if (PklPlacement::withTrashed()->where('partner_id', $item->id)->exists()) {
                    throw new RuntimeException('Mitra ini masih dipakai data PKL (termasuk yang ada di Sampah). Hapus permanen data PKL-nya dulu.');
                }
                foreach (PartnershipDocument::withTrashed()->where('partner_id', $item->id)->get() as $doc) {
                    PartnershipApproval::where('document_id', $doc->id)->delete();
                    $files[] = $doc->file_dokumen;
                    $doc->forceDelete();
                }
                break;

            case 'dokumen':
                PartnershipApproval::where('document_id', $item->id)->delete();
                $files[] = $item->file_dokumen;
                break;

            case 'siswa':
                if (PklPlacement::withTrashed()->where('student_id', $item->id)->exists()) {
                    throw new RuntimeException('Siswa ini masih punya data PKL (termasuk yang ada di Sampah). Hapus permanen data PKL-nya dulu.');
                }
                break;

            case 'pkl':
                if (Certificate::withTrashed()->where('placement_id', $item->id)->exists()) {
                    throw new RuntimeException('Penempatan ini masih punya sertifikat (termasuk yang ada di Sampah). Hapus permanen sertifikatnya dulu.');
                }
                $files[] = $item->surat_permohonan;
                break;

            case 'sertifikat':
                $files[] = $item->file_path;
                break;
        }

        $item->forceDelete();

        return $files;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\PklPlacement;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        // Yang boleh diterbitkan sertifikatnya: PKL selesai, sudah ada nilai, belum pernah diterbitkan
        $eligible = PklPlacement::with(['student.programKeahlian', 'partner'])
            ->where('status', 'selesai')
            ->whereNotNull('nilai')
            ->whereDoesntHave('certificate', fn ($q) => $q->withTrashed())
            ->get();

        $issued = Certificate::with(['placement.student', 'placement.partner'])
            ->latest('tanggal_terbit')
            ->latest('id')
            ->paginate(10);

        $selectedId = $request->query('placement');
        $nomorBerikut = $this->nextNomor();

        return view('sertifikat.index', compact('eligible', 'issued', 'selectedId', 'nomorBerikut'));
    }

    public function store(Request $request)
    {
        $request->validate(['placement_id' => 'required|exists:pkl_placements,id']);

        $placement = PklPlacement::with(['student.programKeahlian', 'partner', 'certificate'])
            ->findOrFail($request->placement_id);

        if ($placement->status !== 'selesai' || $placement->nilai === null) {
            return back()->withErrors(['placement_id' => 'PKL siswa ini belum selesai / belum ada nilai.']);
        }

        // placement_id pada sertifikat bersifat unik, jadi sertifikat yang sudah dihapus (di Sampah)
        // tetap dianggap ada: arahkan ke pemulihan, bukan terbit ulang.
        $sudahAda = Certificate::withTrashed()->where('placement_id', $placement->id)->first();
        if ($sudahAda) {
            $pesan = $sudahAda->trashed()
                ? 'Sertifikat untuk penempatan ini sudah dihapus dan ada di Sampah. Minta Admin memulihkannya, bukan menerbitkan ulang.'
                : 'Sertifikat untuk penempatan ini sudah pernah diterbitkan.';
            return back()->withErrors(['placement_id' => $pesan]);
        }

        $nomor = $this->allocateNomor();
        $token = Str::random(40);
        $verifyUrl = route('sertifikat.verify', $token);

        $kepalaSekolah = User::whereHas('role', fn ($q) => $q->where('name', 'Kepala Sekolah'))->first();

        $pdfBinary = $this->renderPdf([
            'placement' => $placement,
            'nomor' => $nomor,
            'tanggalTerbit' => now(),
            'qrDataUri' => 'data:image/svg+xml;base64,' . base64_encode($this->makeQrSvg($verifyUrl)),
            'namaKepsek' => $kepalaSekolah->name ?? '______________________',
            'logoProvinsi' => $this->logoDataUri('logo-provinsi-kepulauan-riau.png'),
            'logoSekolah' => $this->logoDataUri('logo-smkn4-tanjungpinang.png'),
        ]);

        $path = 'sertifikat/' . Str::lower(str_replace('/', '-', $nomor)) . '.pdf';
        Storage::disk('local')->put($path, $pdfBinary);

        Certificate::create([
            'placement_id' => $placement->id,
            'nomor_sertifikat' => $nomor,
            'tanggal_terbit' => now(),
            'file_path' => $path,
            'qr_code' => $token,
        ]);

        return redirect()->route('sertifikat.index')->with('success', 'Sertifikat ' . $nomor . ' berhasil diterbitkan.');
    }

    public function download(Certificate $certificate)
    {
        abort_unless(in_array(auth()->user()->role->name, ['Admin', 'Waka Humas'], true), 403);
        abort_unless(Storage::disk('local')->exists($certificate->file_path), 404, 'File sertifikat tidak ditemukan. Jalankan migrasi berkas lama terlebih dahulu.');

        return Storage::disk('local')->download($certificate->file_path, str_replace('/', '-', $certificate->nomor_sertifikat) . '.pdf');
    }

    // Halaman publik (tanpa login) yang dibuka saat QR di-scan
    public function verify(string $token)
    {
        $certificate = Certificate::with(['placement.student.programKeahlian', 'placement.partner'])
            ->where('qr_code', $token)
            ->first();

        return view('sertifikat.verifikasi', compact('certificate'));
    }

    private function nextNomor(): string
    {
        // Nomor urut = nomor terakhir tahun ini TERMASUK sertifikat di Sampah (nomor_sertifikat unik).
        $awalan = 'CERT/' . now()->format('Y') . '/';
        $urutanTerbit = (Certificate::withTrashed()->where('nomor_sertifikat', 'like', $awalan . '%')->pluck('nomor_sertifikat')
            ->map(fn ($n) => (int) last(explode('/', $n)))->max() ?? 0) + 1;
        $urutanTersimpan = ((int) DB::table('document_number_sequences')->where('name', 'certificate:' . now()->format('Y'))->value('last_number')) + 1;

        return sprintf('CERT/%s/%s/%04d', now()->format('Y'), now()->format('m'), max($urutanTerbit, $urutanTersimpan));
    }

    private function allocateNomor(): string
    {
        $year = now()->format('Y');
        $sequenceName = 'certificate:' . $year;
        $prefix = 'CERT/' . $year . '/';
        $existingMaximum = DB::table('document_number_sequences')->where('name', $sequenceName)->exists()
            ? 0
            : ((Certificate::withTrashed()
                ->where('nomor_sertifikat', 'like', $prefix . '%')
                ->pluck('nomor_sertifikat')
                ->map(fn ($number) => (int) last(explode('/', $number)))
                ->max()) ?? 0);

        return DB::transaction(function () use ($year, $sequenceName, $existingMaximum) {
            DB::table('document_number_sequences')->insertOrIgnore([
                'name' => $sequenceName,
                'last_number' => $existingMaximum,
            ]);
            DB::table('document_number_sequences')->where('name', $sequenceName)
                ->where('last_number', '<', $existingMaximum)
                ->update(['last_number' => $existingMaximum]);
            DB::table('document_number_sequences')->where('name', $sequenceName)->increment('last_number');
            $sequence = DB::table('document_number_sequences')->where('name', $sequenceName)->value('last_number');

            return sprintf('CERT/%s/%s/%04d', $year, now()->format('m'), $sequence);
        });
    }

    private function makeQrSvg(string $text): string
    {
        $renderer = new ImageRenderer(new RendererStyle(240, 0), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($text);
    }

    private function logoDataUri(string $filename): string
    {
        $path = public_path('images/' . $filename);
        abort_unless(is_file($path), 500, 'Logo dokumen tidak ditemukan.');

        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }

    private function renderPdf(array $data): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('sertifikat.pdf', $data)->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    public function destroy(Certificate $certificate)
    {
        if (! in_array(auth()->user()->role->name, ['Admin', 'Waka Humas'])) {
            abort(403, 'Akses ditolak');
        }

        $certificate->delete();

        return redirect()->route('sertifikat.index')
            ->with('success', 'Sertifikat berhasil dihapus dari daftar.');
    }
}

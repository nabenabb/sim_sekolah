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
            ->whereDoesntHave('certificate')
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

        if ($placement->certificate) {
            return back()->withErrors(['placement_id' => 'Sertifikat untuk penempatan ini sudah pernah diterbitkan.']);
        }

        $nomor = $this->nextNomor();
        $token = Str::random(40);
        $verifyUrl = route('sertifikat.verify', $token);

        $kepalaSekolah = User::whereHas('role', fn ($q) => $q->where('name', 'Kepala Sekolah'))->first();

        $pdfBinary = $this->renderPdf([
            'placement' => $placement,
            'nomor' => $nomor,
            'tanggalTerbit' => now(),
            'qrDataUri' => 'data:image/svg+xml;base64,' . base64_encode($this->makeQrSvg($verifyUrl)),
            'namaKepsek' => $kepalaSekolah->name ?? '______________________',
        ]);

        $path = 'sertifikat/' . Str::lower(str_replace('/', '-', $nomor)) . '.pdf';
        Storage::disk('public')->put($path, $pdfBinary);

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
        $fullPath = Storage::disk('public')->path($certificate->file_path);

        abort_unless(file_exists($fullPath), 404, 'File sertifikat tidak ditemukan.');

        return response()->download($fullPath, str_replace('/', '-', $certificate->nomor_sertifikat) . '.pdf');
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
        $urutan = Certificate::whereYear('tanggal_terbit', now()->year)->count() + 1;

        return sprintf('CERT/%s/%s/%04d', now()->format('Y'), now()->format('m'), $urutan);
    }

    private function makeQrSvg(string $text): string
    {
        $renderer = new ImageRenderer(new RendererStyle(240, 0), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($text);
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
}

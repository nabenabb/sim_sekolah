<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\PklPlacement;
use App\Models\User;
use DateTimeInterface;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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

    public function store(Request $request): RedirectResponse|StreamedResponse
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

        if (! $placement->student || ! $placement->partner) {
            return back()->withErrors(['placement_id' => 'Data siswa atau mitra pada penempatan PKL tidak lengkap. Periksa data sebelum menerbitkan sertifikat.']);
        }

        $nomor = $this->allocateNomor();
        $token = Str::random(40);
        $tanggalTerbit = now();
        $path = 'sertifikat/' . Str::lower(str_replace('/', '-', $nomor)) . '.pdf';
        $disk = Storage::disk('local');

        try {
            $pdfBinary = $this->renderPdf($this->certificatePdfData($placement, $nomor, $tanggalTerbit, $token));
            if (! $disk->put($path, $pdfBinary)) {
                throw new RuntimeException('Unable to store the generated certificate PDF.');
            }

            DB::transaction(fn (): Certificate => Certificate::create([
                'placement_id' => $placement->id,
                'nomor_sertifikat' => $nomor,
                'tanggal_terbit' => $tanggalTerbit,
                'file_path' => $path,
                'qr_code' => $token,
            ]));
        } catch (Throwable $exception) {
            try {
                $disk->delete($path);
            } catch (Throwable $cleanupException) {
                Log::warning('certificate.cleanup_failed', [
                    'placement_id' => $placement->id,
                    'exception' => $cleanupException,
                ]);
            }

            Log::error('certificate.issue_failed', [
                'placement_id' => $placement->id,
                'exception' => $exception,
            ]);

            return back()->withInput()->withErrors(['placement_id' => 'Sertifikat gagal diterbitkan. Coba lagi, atau hubungi Admin jika masalah berlanjut.']);
        }

        return $this->streamPdf($pdfBinary, $nomor);
    }

    public function download(Certificate $certificate): StreamedResponse
    {
        abort_unless(in_array(auth()->user()->role->name, ['Admin', 'Waka Humas'], true), 403);

        $certificate->loadMissing('placement.student.programKeahlian', 'placement.partner');
        $placement = $certificate->placement;
        abort_unless($placement && $placement->student && $placement->partner, 404, 'Data sertifikat tidak lengkap.');

        $disk = Storage::disk('local');

        try {
            $pdfBinary = $disk->exists($certificate->file_path) ? $disk->get($certificate->file_path) : '';
            if (! str_starts_with($pdfBinary, '%PDF-')) {
                $pdfBinary = $this->renderPdf($this->certificatePdfData(
                    $placement,
                    $certificate->nomor_sertifikat,
                    $certificate->tanggal_terbit,
                    $certificate->qr_code,
                ));
                $disk->put($certificate->file_path, $pdfBinary);
            }
        } catch (Throwable $exception) {
            Log::error('certificate.download_failed', [
                'certificate_id' => $certificate->id,
                'exception' => $exception,
            ]);

            abort(503, 'Sertifikat belum dapat dibuat. Silakan coba lagi beberapa saat.');
        }

        return $this->streamPdf($pdfBinary, $certificate->nomor_sertifikat);
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
        if (! is_file($path)) {
            throw new RuntimeException('Certificate logo is missing: ' . $filename);
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Certificate logo could not be read: ' . $filename);
        }

        return 'data:image/png;base64,' . base64_encode($contents);
    }

    /**
     * @return array{placement: PklPlacement, nomor: string, tanggalTerbit: DateTimeInterface, qrDataUri: string, namaKepsek: string, logoProvinsi: string, logoSekolah: string}
     */
    private function certificatePdfData(PklPlacement $placement, string $nomor, DateTimeInterface $tanggalTerbit, string $token): array
    {
        $kepalaSekolah = User::whereHas('role', fn ($query) => $query->where('name', 'Kepala Sekolah'))->first();

        return [
            'placement' => $placement,
            'nomor' => $nomor,
            'tanggalTerbit' => $tanggalTerbit,
            'qrDataUri' => 'data:image/svg+xml;base64,' . base64_encode($this->makeQrSvg(route('sertifikat.verify', $token))),
            'namaKepsek' => $kepalaSekolah->name ?? '______________________',
            'logoProvinsi' => $this->logoDataUri('logo-provinsi-kepulauan-riau.png'),
            'logoSekolah' => $this->logoDataUri('logo-smkn4-tanjungpinang.png'),
        ];
    }

    private function streamPdf(string $pdfBinary, string $nomor): StreamedResponse
    {
        $filename = str_replace('/', '-', $nomor) . '.pdf';

        return response()->streamDownload(static function () use ($pdfBinary): void {
            echo $pdfBinary;
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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

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
use App\Models\ProgramKeahlian;
use App\Models\Student;
use App\Models\User;
use App\Support\DocumentNumberGenerator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanTransferController extends Controller
{
    private const REPORT_TYPES = ['ringkasan', 'surat-masuk', 'surat-keluar', 'mitra', 'pkl', 'sertifikat'];
    private const PREVIEW_ROWS = 20;

    private const IMPORT_HEADERS = [
        'surat-masuk' => ['tanggal_terima', 'pengirim', 'nomor_surat', 'tanggal_surat', 'sifat', 'perihal'],
        'surat-keluar' => ['tanggal', 'tujuan', 'perihal', 'isi_ringkas', 'ref_no_agenda'],
        'mitra' => ['nama_perusahaan', 'alamat_pusat', 'bidang_usaha', 'nama_jurusan', 'kontak_person', 'no_telepon', 'jenis_dokumen', 'nomor_dokumen', 'ruang_lingkup', 'berlaku_mulai', 'berlaku_selesai'],
        'pkl' => ['nis', 'nama_perusahaan', 'email_pembimbing', 'pembimbing_industri', 'tanggal_mulai', 'tanggal_selesai', 'status', 'nilai'],
    ];

    private const IMPORT_ROLES = [
        'surat-masuk' => ['Admin', 'Koordinator TU'],
        'surat-keluar' => ['Admin', 'Koordinator TU'],
        'mitra' => ['Admin', 'Waka Humas'],
        'pkl' => ['Admin', 'Waka Humas'],
    ];

    public function export(Request $request, string $jenis): StreamedResponse
    {
        $filters = Validator::make(array_merge($request->query(), ['jenis' => $jenis]), [
            'jenis' => 'required|in:' . implode(',', self::REPORT_TYPES),
            'dari' => 'nullable|date_format:Y-m-d',
            'sampai' => 'nullable|date_format:Y-m-d',
        ])->validate();
        [$from, $to] = $this->dateRange($filters['dari'] ?? null, $filters['sampai'] ?? null);
        $type = $filters['jenis'];
        $filename = sprintf('laporan-%s-%s-%s.csv', $type, $from->toDateString(), $to->toDateString());
        Log::info('report.exported', ['user_id' => auth()->id(), 'type' => $type, 'format' => 'csv', 'from' => $from->toDateString(), 'to' => $to->toDateString()]);

        return response()->streamDownload(function () use ($type, $from, $to): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            foreach ($this->exportRows($type, $from, $to) as $row) {
                fputcsv($output, array_map($this->safeCsvCell(...), $row), ',', '"', '');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request, string $jenis): JsonResponse
    {
        $filters = Validator::make(array_merge($request->query(), ['jenis' => $jenis]), [
            'jenis' => 'required|in:' . implode(',', self::REPORT_TYPES),
            'dari' => 'nullable|date_format:Y-m-d',
            'sampai' => 'nullable|date_format:Y-m-d',
        ])->validate();
        [$from, $to] = $this->dateRange($filters['dari'] ?? null, $filters['sampai'] ?? null);

        $headers = [];
        $rows = [];
        $hasMore = false;
        foreach ($this->exportRows($filters['jenis'], $from, $to) as $row) {
            if ($headers === []) {
                $headers = $row;
                continue;
            }
            if (count($rows) >= self::PREVIEW_ROWS) {
                $hasMore = true;
                break;
            }
            $rows[] = array_map(static fn ($value) => $value === null ? '' : (string) $value, $row);
        }

        $titles = [
            'ringkasan' => 'Ringkasan Laporan', 'surat-masuk' => 'Laporan Surat Masuk',
            'surat-keluar' => 'Laporan Surat Keluar', 'mitra' => 'Laporan Mitra dan Kerja Sama',
            'pkl' => 'Laporan Penempatan PKL', 'sertifikat' => 'Laporan Sertifikat',
        ];

        return response()->json([
            'title' => $titles[$filters['jenis']],
            'headers' => $headers,
            'rows' => $rows,
            'has_more' => $hasMore,
            'limit' => self::PREVIEW_ROWS,
        ]);
    }

    public function exportPdf(Request $request, string $jenis): StreamedResponse|RedirectResponse
    {
        $filters = Validator::make(array_merge($request->query(), ['jenis' => $jenis]), [
            'jenis' => 'required|in:' . implode(',', self::REPORT_TYPES),
            'dari' => 'nullable|date_format:Y-m-d',
            'sampai' => 'nullable|date_format:Y-m-d',
        ])->validate();
        [$from, $to] = $this->dateRange($filters['dari'] ?? null, $filters['sampai'] ?? null);
        $headers = [];
        $rows = [];
        foreach ($this->exportRows($filters['jenis'], $from, $to) as $row) {
            if ($headers === []) {
                $headers = $row;
                continue;
            }
            if (count($rows) >= 2000) {
                return redirect()->route('laporan.index', [
                    'jenis' => $filters['jenis'], 'dari' => $from->toDateString(), 'sampai' => $to->toDateString(),
                ])->withErrors(['pdf' => 'PDF dibatasi 2.000 baris agar file tetap ringan. Gunakan Ekspor CSV untuk data yang lebih banyak.']);
            }
            $rows[] = $row;
        }

        $titles = [
            'ringkasan' => 'Ringkasan Laporan', 'surat-masuk' => 'Laporan Surat Masuk',
            'surat-keluar' => 'Laporan Surat Keluar', 'mitra' => 'Laporan Mitra dan Kerja Sama',
            'pkl' => 'Laporan Penempatan PKL', 'sertifikat' => 'Laporan Sertifikat',
        ];
        Log::info('report.exported', ['user_id' => auth()->id(), 'type' => $filters['jenis'], 'format' => 'pdf', 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'rows' => count($rows)]);
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('laporan.pdf', [
            'title' => $titles[$filters['jenis']],
            'headers' => $headers,
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
        ])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text($canvas->get_width() - 92, $canvas->get_height() - 18, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 7, [113, 133, 154]);

        $filename = sprintf('laporan-%s-%s-%s.pdf', $filters['jenis'], $from->toDateString(), $to->toDateString());
        $pdf = $dompdf->output();

        return response()->streamDownload(static function () use ($pdf): void {
            echo $pdf;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function template(string $type): StreamedResponse
    {
        abort_unless(isset(self::IMPORT_HEADERS[$type]), 404);
        $this->authorizeImport($type);

        return response()->streamDownload(function () use ($type): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, self::IMPORT_HEADERS[$type], ',', '"', '');
            fclose($output);
        }, 'template-impor-' . $type . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'jenis_import' => 'required|in:' . implode(',', array_keys(self::IMPORT_HEADERS)),
            'file_import' => 'required|file|mimes:csv,txt|max:10240',
        ], [
            'jenis_import.required' => 'Pilih jenis data yang akan diimpor.',
            'jenis_import.in' => 'Jenis data impor tidak dikenali.',
            'file_import.required' => 'Pilih file CSV terlebih dahulu.',
            'file_import.file' => 'File yang diunggah tidak dapat dibaca.',
            'file_import.mimes' => 'File harus berformat CSV.',
            'file_import.max' => 'Ukuran file CSV maksimal 10 MB.',
        ], [
            'jenis_import' => 'jenis data', 'file_import' => 'file CSV',
        ])->validate();
        $type = $validated['jenis_import'];
        $this->authorizeImport($type);

        [$rows, $parseErrors] = $this->readCsv($request->file('file_import')->getRealPath(), self::IMPORT_HEADERS[$type]);
        if ($parseErrors !== []) {
            return $this->importError($type, $parseErrors);
        }

        if ($rows === []) {
            return $this->importError($type, ['File CSV tidak berisi baris data.']);
        }

        if (count($rows) > 1000) {
            return $this->importError($type, ['Maksimal 1.000 baris data per file. Pecah file menjadi beberapa bagian.']);
        }

        [$records, $errors] = $this->validateRows($type, $rows);
        if ($errors !== []) {
            return $this->importError($type, $errors);
        }

        try {
            DB::transaction(function () use ($type, $records): void {
                foreach ($records as $record) {
                    $this->persistRecord($type, $record);
                }
            });
        } catch (QueryException) {
            return $this->importError($type, ['Data berubah atau bertabrakan saat penyimpanan. Tidak ada baris yang disimpan; muat ulang data referensi dan coba lagi.']);
        }

        Log::info('report.imported', ['user_id' => auth()->id(), 'type' => $type, 'rows' => count($records)]);

        return redirect()->route('laporan.index', ['jenis' => $type])
            ->with('success', count($records) . ' baris berhasil diimpor. Data ditambahkan sebagai data baru.');
    }

    private function exportRows(string $type, Carbon $from, Carbon $to): iterable
    {
        if ($type === 'ringkasan') {
            yield ['indikator', 'jumlah', 'catatan'];
            yield ['surat_masuk', IncomingLetter::whereBetween('tanggal_terima', [$from, $to])->count(), 'Dalam periode'];
            yield ['surat_keluar', OutgoingLetter::whereBetween('tanggal', [$from, $to])->count(), 'Dalam periode'];
            yield ['mitra_aktif', IndustryPartner::where('status', 'aktif')->count(), 'Kondisi saat ini'];
            yield ['dokumen_kerja_sama_berlaku', PartnershipDocument::where('status', 'disetujui')->whereDate('berlaku_selesai', '>=', today())->count(), 'Kondisi saat ini'];
            yield ['pkl_berjalan', PklPlacement::where('status', 'berjalan')->count(), 'Kondisi saat ini'];
            yield ['pkl_selesai', PklPlacement::where('status', 'selesai')->whereBetween('tanggal_selesai', [$from, $to])->count(), 'Selesai dalam periode'];
            yield ['sertifikat_terbit', Certificate::whereBetween('tanggal_terbit', [$from, $to])->count(), 'Dalam periode'];
            foreach (OutgoingLetter::whereBetween('tanggal', [$from, $to])->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') as $status => $total) {
                yield ['surat_keluar_status_' . $status, $total, 'Dalam periode'];
            }
            foreach (PklPlacement::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') as $status => $total) {
                yield ['pkl_status_' . $status, $total, 'Kondisi saat ini'];
            }

            return;
        }

        $headers = match ($type) {
            'surat-masuk' => ['no_agenda', 'tanggal_terima', 'pengirim', 'nomor_surat', 'tanggal_surat', 'sifat', 'perihal', 'status_disposisi'],
            'surat-keluar' => ['no_surat', 'tanggal', 'tujuan', 'perihal', 'jenis_template', 'status', 'isi_ringkas', 'referensi_no_agenda'],
            'mitra' => ['kategori_laporan', 'nama_perusahaan', 'program_keahlian', 'jenis_dokumen', 'nomor_dokumen', 'ruang_lingkup', 'berlaku_mulai', 'berlaku_selesai', 'status'],
            'pkl' => ['nis', 'nama_siswa', 'nama_perusahaan', 'email_pembimbing', 'tanggal_mulai', 'tanggal_selesai', 'status', 'nilai'],
            'sertifikat' => ['nomor_sertifikat', 'tanggal_terbit', 'nis', 'nama_siswa', 'nama_perusahaan', 'nilai'],
        };
        yield $headers;

        $query = match ($type) {
            'surat-masuk' => IncomingLetter::with('dispositions')->whereBetween('tanggal_terima', [$from, $to])->orderBy('tanggal_terima'),
            'surat-keluar' => OutgoingLetter::with('refLetter')->whereBetween('tanggal', [$from, $to])->orderBy('tanggal'),
            'mitra' => PartnershipDocument::with('partner.programKeahlian')->where(function ($query) use ($from, $to) {
                $query->whereBetween('berlaku_mulai', [$from, $to])->orWhereBetween('created_at', [$from, $to]);
            })->orWhere(function ($query) {
                $query->where('status', 'disetujui')
                    ->whereBetween('berlaku_selesai', [today(), today()->addDays(60)]);
            })->orderBy('berlaku_mulai'),
            'pkl' => PklPlacement::with(['student', 'partner', 'pembimbing'])->where('tanggal_mulai', '<=', $to)->where('tanggal_selesai', '>=', $from)->orderBy('tanggal_mulai'),
            'sertifikat' => Certificate::with(['placement.student', 'placement.partner'])->whereBetween('tanggal_terbit', [$from, $to])->orderBy('tanggal_terbit'),
        };

        foreach ($query->lazy(500) as $item) {
            yield match ($type) {
                'surat-masuk' => [
                    $item->no_agenda, $item->tanggal_terima?->toDateString(), $item->pengirim,
                    $item->nomor_surat, $item->tanggal_surat?->toDateString(), $item->sifat,
                    $item->perihal, $this->incomingStatus($item),
                ],
                'surat-keluar' => [
                    $item->no_surat, $item->tanggal?->toDateString(), $item->tujuan, $item->perihal,
                    $item->jenis_template, $item->status, $item->isi_ringkas, $item->refLetter?->no_agenda,
                ],
                'mitra' => [
                    $this->partnershipReportCategory($item, $from, $to),
                    $item->partner?->nama_perusahaan, $item->partner?->programKeahlian?->nama_jurusan,
                    $item->jenis_dokumen, $item->nomor_dokumen, $item->ruang_lingkup,
                    $item->berlaku_mulai?->toDateString(), $item->berlaku_selesai?->toDateString(), $item->status,
                ],
                'pkl' => [
                    $item->student?->nis, $item->student?->nama, $item->partner?->nama_perusahaan,
                    $item->pembimbing?->email, $item->tanggal_mulai?->toDateString(),
                    $item->tanggal_selesai?->toDateString(), $item->status, $item->nilai,
                ],
                'sertifikat' => [
                    $item->nomor_sertifikat, $item->tanggal_terbit?->toDateString(),
                    $item->placement?->student?->nis, $item->placement?->student?->nama,
                    $item->placement?->partner?->nama_perusahaan, $item->placement?->nilai,
                ],
            };
        }
    }

    private function incomingStatus(IncomingLetter $letter): string
    {
        if ($letter->dispositions->isEmpty()) {
            return 'menunggu';
        }

        return $letter->dispositions->every(fn (Disposition $disposition) => $disposition->status === 'selesai')
            ? 'selesai'
            : 'diproses';
    }

    private function partnershipReportCategory(PartnershipDocument $document, Carbon $from, Carbon $to): string
    {
        $inPeriod = ($document->berlaku_mulai && $document->berlaku_mulai >= $from && $document->berlaku_mulai <= $to)
            || ($document->created_at && $document->created_at >= $from && $document->created_at <= $to);
        $expiringSoon = $document->status === 'disetujui'
            && $document->berlaku_selesai
            && $document->berlaku_selesai >= today()
            && $document->berlaku_selesai <= today()->addDays(60);

        return match (true) {
            $inPeriod && $expiringSoon => 'periode_dan_akan_kadaluarsa',
            $expiringSoon => 'akan_kadaluarsa',
            default => 'periode',
        };
    }

    private function readCsv(string $path, array $expectedHeaders): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [[], ['File CSV tidak dapat dibaca.']];
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [[], ['File CSV kosong.']];
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);
        $headers = fgetcsv($handle, null, $delimiter, '"', '');
        if (! is_array($headers)) {
            fclose($handle);
            return [[], ['Baris header tidak valid.']];
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        if ($headers !== $expectedHeaders) {
            fclose($handle);
            return [[], ['Header tidak sesuai template. Unduh template untuk jenis impor yang dipilih.']];
        }

        $rows = [];
        $errors = [];
        $line = 1;
        while (($values = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            if (count($values) !== count($headers)) {
                $errors[] = "Baris {$line}: jumlah kolom tidak sesuai header.";
                continue;
            }
            $rows[] = ['line' => $line, 'data' => array_combine($headers, array_map(fn ($value) => trim((string) $value), $values))];
        }
        fclose($handle);

        return [$rows, $errors];
    }

    private function validateRows(string $type, array $rows): array
    {
        $records = [];
        $errors = [];
        $seen = [];

        foreach ($rows as $rowData) {
            $row = array_map(fn ($value) => $value === '' ? null : $value, $rowData['data']);
            $line = $rowData['line'];
            $rules = match ($type) {
                'surat-masuk' => [
                    'tanggal_terima' => 'required|date_format:Y-m-d', 'pengirim' => 'required|string|max:150',
                    'nomor_surat' => 'nullable|string|max:50', 'tanggal_surat' => 'nullable|date_format:Y-m-d',
                    'sifat' => 'required|in:Biasa,Segera,Sangat Segera,Rahasia', 'perihal' => 'required|string|max:255',
                ],
                'surat-keluar' => [
                    'tanggal' => 'required|date_format:Y-m-d', 'tujuan' => 'required|string|max:150',
                    'perihal' => 'required|string|max:255', 'isi_ringkas' => 'nullable|string',
                    'ref_no_agenda' => 'nullable|string|max:30',
                ],
                'mitra' => [
                    'nama_perusahaan' => 'required|string|max:150', 'alamat_pusat' => 'required|string|max:255',
                    'bidang_usaha' => 'required|string|max:100', 'nama_jurusan' => 'required|string|max:100',
                    'kontak_person' => 'required|string|max:100', 'no_telepon' => 'nullable|string|max:30',
                    'jenis_dokumen' => 'required|in:MoU,PKS,MoA', 'nomor_dokumen' => 'nullable|string|max:100',
                    'ruang_lingkup' => 'nullable|string|max:255', 'berlaku_mulai' => 'required|date_format:Y-m-d',
                    'berlaku_selesai' => 'required|date_format:Y-m-d|after:berlaku_mulai',
                ],
                'pkl' => [
                    'nis' => 'required|string|max:50', 'nama_perusahaan' => 'required|string|max:150',
                    'email_pembimbing' => 'required|email|max:100', 'pembimbing_industri' => 'nullable|string|max:100',
                    'tanggal_mulai' => 'required|date_format:Y-m-d', 'tanggal_selesai' => 'required|date_format:Y-m-d|after:tanggal_mulai',
                    'status' => 'required|in:berjalan,selesai,dibatalkan', 'nilai' => 'nullable|numeric|min:0|max:100',
                ],
            };

            $attributes = [
                'tanggal_terima' => 'tanggal terima', 'pengirim' => 'pengirim', 'nomor_surat' => 'nomor surat',
                'tanggal_surat' => 'tanggal surat', 'sifat' => 'sifat surat', 'perihal' => 'perihal',
                'tanggal' => 'tanggal', 'tujuan' => 'tujuan', 'isi_ringkas' => 'isi ringkas',
                'ref_no_agenda' => 'nomor agenda referensi', 'nama_perusahaan' => 'nama perusahaan',
                'alamat_pusat' => 'alamat perusahaan', 'bidang_usaha' => 'bidang usaha',
                'nama_jurusan' => 'program keahlian', 'kontak_person' => 'kontak person', 'no_telepon' => 'nomor telepon',
                'jenis_dokumen' => 'jenis dokumen', 'nomor_dokumen' => 'nomor dokumen', 'ruang_lingkup' => 'ruang lingkup',
                'berlaku_mulai' => 'tanggal mulai berlaku', 'berlaku_selesai' => 'tanggal akhir berlaku',
                'nis' => 'NIS siswa', 'email_pembimbing' => 'email pembimbing', 'pembimbing_industri' => 'nama pembimbing industri',
                'tanggal_mulai' => 'tanggal mulai', 'tanggal_selesai' => 'tanggal selesai', 'status' => 'status', 'nilai' => 'nilai',
            ];
            $messages = [
                'required' => ':attribute wajib diisi.', 'date_format' => ':attribute harus berformat YYYY-MM-DD.',
                'string' => ':attribute harus berupa teks.', 'max' => ':attribute terlalu panjang.',
                'in' => 'Nilai :attribute tidak sesuai pilihan yang tersedia.', 'email' => 'Format :attribute tidak valid.',
                'numeric' => ':attribute harus berupa angka.', 'min' => ':attribute tidak boleh kurang dari 0.',
                'max.numeric' => ':attribute tidak boleh lebih dari 100.', 'after' => ':attribute harus setelah tanggal mulai.',
            ];
            $validator = Validator::make($row, $rules, $messages, $attributes);
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Baris {$line}: {$message}";
                }
                continue;
            }

            $record = $this->resolveImportRecord($type, $row, $line, $errors, $seen);
            if ($record !== null) {
                $records[] = $record;
            }
        }

        return [$records, $errors];
    }

    private function resolveImportRecord(string $type, array $row, int $line, array &$errors, array &$seen): ?array
    {
        if ($type === 'surat-masuk') {
            $key = implode('|', [$row['tanggal_terima'], mb_strtolower($row['pengirim']), mb_strtolower($row['nomor_surat'] ?? ''), mb_strtolower($row['perihal'])]);
            $duplicate = IncomingLetter::query()
                ->whereDate('tanggal_terima', $row['tanggal_terima'])
                ->where('pengirim', $row['pengirim'])
                ->where('nomor_surat', $row['nomor_surat'])
                ->where('perihal', $row['perihal'])
                ->exists();
            if (isset($seen[$key]) || $duplicate) {
                $errors[] = "Baris {$line}: surat masuk dengan data yang sama sudah ada atau terduplikasi di file.";
                return null;
            }
            $seen[$key] = true;
        }

        if ($type === 'surat-keluar' && $row['ref_no_agenda']) {
            $reference = IncomingLetter::where('no_agenda', $row['ref_no_agenda'])->first();
            if (! $reference) {
                $errors[] = "Baris {$line}: nomor agenda referensi tidak ditemukan.";
                return null;
            }
            $row['ref_incoming_letter_id'] = $reference->id;
        }

        if ($type === 'mitra') {
            $key = mb_strtolower($row['nama_perusahaan']);
            $program = ProgramKeahlian::where('nama_jurusan', $row['nama_jurusan'])->first();
            if (! $program) {
                $errors[] = "Baris {$line}: program keahlian tidak ditemukan.";
                return null;
            }
            if (isset($seen[$key]) || IndustryPartner::withTrashed()->whereRaw('LOWER(nama_perusahaan) = ?', [$key])->exists()) {
                $errors[] = "Baris {$line}: mitra dengan nama tersebut sudah ada atau terduplikasi di file.";
                return null;
            }
            $seen[$key] = true;
            $row['program_keahlian_id'] = $program->id;
        }

        if ($type === 'pkl') {
            $student = Student::where('nis', $row['nis'])->first();
            $partner = IndustryPartner::where('nama_perusahaan', $row['nama_perusahaan'])->where('status', 'aktif')->first();
            $teacher = User::with('role')->where('email', $row['email_pembimbing'])->where('is_active', true)->first();
            if (! $student || ! $partner || ! $teacher || ! in_array($teacher->role->name ?? null, ['Guru', 'Kepala Program Keahlian'], true)) {
                $errors[] = "Baris {$line}: NIS, mitra aktif, atau email pembimbing tidak ditemukan/aktif.";
                return null;
            }
            $key = $student->id . ':' . $row['tanggal_mulai'] . ':' . $row['tanggal_selesai'];
            $duplicatePlacement = PklPlacement::withTrashed()
                ->where('student_id', $student->id)->where('partner_id', $partner->id)
                ->whereDate('tanggal_mulai', $row['tanggal_mulai'])
                ->whereDate('tanggal_selesai', $row['tanggal_selesai'])
                ->exists();
            if (isset($seen[$key]) || $duplicatePlacement || ($row['status'] === 'berjalan' && PklPlacement::where('student_id', $student->id)->where('status', 'berjalan')->exists())) {
                $errors[] = "Baris {$line}: penempatan PKL ganda atau siswa sudah memiliki PKL berjalan.";
                return null;
            }
            if ($row['status'] === 'selesai' && $row['nilai'] === null) {
                $errors[] = "Baris {$line}: nilai wajib diisi untuk PKL berstatus selesai.";
                return null;
            }
            if ($row['status'] === 'berjalan' && isset($seen['active:' . $student->id])) {
                $errors[] = "Baris {$line}: file memuat lebih dari satu PKL berjalan untuk siswa yang sama.";
                return null;
            }
            $seen[$key] = true;
            if ($row['status'] === 'berjalan') {
                $seen['active:' . $student->id] = true;
            }
            $row['student_id'] = $student->id;
            $row['partner_id'] = $partner->id;
            $row['pembimbing_id'] = $teacher->id;
        }

        return $row;
    }

    private function persistRecord(string $type, array $row): void
    {
        if ($type === 'surat-masuk') {
            IncomingLetter::create([
                'no_agenda' => app(DocumentNumberGenerator::class)->incomingAgenda(),
                'tanggal_terima' => $row['tanggal_terima'], 'pengirim' => $row['pengirim'],
                'nomor_surat' => $row['nomor_surat'], 'tanggal_surat' => $row['tanggal_surat'],
                'sifat' => $row['sifat'], 'perihal' => $row['perihal'], 'created_by' => auth()->id(),
            ]);
            return;
        }

        if ($type === 'surat-keluar') {
            OutgoingLetter::create([
                'no_surat' => app(DocumentNumberGenerator::class)->outgoingLetter(),
                'tanggal' => $row['tanggal'], 'tujuan' => $row['tujuan'], 'perihal' => $row['perihal'],
                'isi_ringkas' => $row['isi_ringkas'], 'jenis_template' => 'umum', 'status' => 'draf',
                'ref_incoming_letter_id' => $row['ref_incoming_letter_id'] ?? null, 'created_by' => auth()->id(),
            ]);
            return;
        }

        if ($type === 'mitra') {
            $partner = IndustryPartner::create([
                'nama_perusahaan' => $row['nama_perusahaan'], 'alamat_pusat' => $row['alamat_pusat'],
                'bidang_usaha' => $row['bidang_usaha'], 'program_keahlian_id' => $row['program_keahlian_id'],
                'kontak_person' => $row['kontak_person'], 'no_telepon' => $row['no_telepon'], 'status' => 'aktif',
            ]);
            $document = PartnershipDocument::create([
                'partner_id' => $partner->id, 'jenis_dokumen' => $row['jenis_dokumen'],
                'nomor_dokumen' => $row['nomor_dokumen'], 'ruang_lingkup' => $row['ruang_lingkup'],
                'berlaku_mulai' => $row['berlaku_mulai'], 'berlaku_selesai' => $row['berlaku_selesai'],
                'status' => 'diajukan',
            ]);
            foreach (array_keys(PartnershipApproval::TAHAP_ROLE) as $stage) {
                PartnershipApproval::create(['document_id' => $document->id, 'tahap' => $stage, 'status' => 'menunggu']);
            }
            return;
        }

        PklPlacement::create([
            'student_id' => $row['student_id'], 'partner_id' => $row['partner_id'],
            'pembimbing_id' => $row['pembimbing_id'], 'pembimbing_industri' => $row['pembimbing_industri'],
            'tanggal_mulai' => $row['tanggal_mulai'], 'tanggal_selesai' => $row['tanggal_selesai'],
            'status' => $row['status'], 'nilai' => $row['nilai'],
        ]);
    }

    private function authorizeImport(string $type): void
    {
        abort_unless(in_array(auth()->user()->role->name, self::IMPORT_ROLES[$type] ?? [], true), 403, 'Role Anda tidak boleh mengimpor data ini.');
    }

    private function importError(string $type, array $errors): RedirectResponse
    {
        return redirect()->route('laporan.index', ['jenis' => $type])
            ->with('import_issues', array_slice($errors, 0, 30))
            ->with('error', 'Tidak ada data yang diimpor. Perbaiki kesalahan CSV lalu coba lagi.');
    }

    private function dateRange(?string $from, ?string $to): array
    {
        $fromDate = $from ? Carbon::createFromFormat('Y-m-d', $from)->startOfDay() : now()->startOfMonth();
        $toDate = $to ? Carbon::createFromFormat('Y-m-d', $to)->endOfDay() : now()->endOfDay();
        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        return [$fromDate, $toDate];
    }

    private function safeCsvCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value ?? '';
        }

        return preg_match('/^[\s]*[=+@\-]/u', $value) === 1 ? "'" . $value : $value;
    }
}

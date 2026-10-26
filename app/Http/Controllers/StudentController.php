<?php

namespace App\Http\Controllers;

use App\Models\ProgramKeahlian;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    private const CSV_HEADERS = ['nis', 'nama', 'tanggal_lahir', 'nama_jurusan'];

    public function index(Request $request): View
    {
        $filters = Validator::make($request->query(), [
            'q' => 'nullable|string|max:100',
            'jurusan' => 'nullable|integer|exists:program_keahlian,id',
            'edit' => 'nullable|integer|exists:students,id',
        ], $this->validationMessages())->validate();
        $query = $this->filteredStudents($filters)->with('programKeahlian')->orderBy('nama');
        $students = $query->paginate(15)->withQueryString();
        $programKeahlian = ProgramKeahlian::orderBy('nama_jurusan')->get();

        $editing = isset($filters['edit'])
            ? Student::find($filters['edit'])
            : null;

        return view('siswa.index', compact('students', 'programKeahlian', 'editing'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'nis' => 'required|string|max:20|unique:students,nis',
            'tanggal_lahir' => 'nullable|date',
            'program_keahlian_id' => 'required|exists:program_keahlian,id',
        ], $this->validationMessages())->validate();

        $student = Student::create($data);
        Log::info('student.created', ['user_id' => auth()->id(), 'student_id' => $student->id]);

        return redirect()->route('siswa.index')
            ->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Student $siswa): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'nis' => [
                'required',
                'string',
                'max:20',
                Rule::unique('students', 'nis')->ignore($siswa->id),
            ],
            'tanggal_lahir' => 'nullable|date',
            'program_keahlian_id' => 'required|exists:program_keahlian,id',
        ], $this->validationMessages())->validate();

        $siswa->update($data);
        Log::info('student.updated', ['user_id' => auth()->id(), 'student_id' => $siswa->id]);

        return redirect()->route('siswa.index')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $siswa): RedirectResponse
    {
        // Cegah hapus jika masih punya penempatan aktif
        $masihAktif = $siswa->placements()->where('status', 'berjalan')->exists();
        if ($masihAktif) {
            return back()->withErrors([
                'siswa' => 'Siswa masih memiliki penempatan PKL yang berjalan. Selesaikan/batalkan dulu.',
            ]);
        }

        $siswa->delete(); // soft delete
        Log::info('student.archived', ['user_id' => auth()->id(), 'student_id' => $siswa->id]);

        return redirect()->route('siswa.index')
            ->with('success', 'Siswa berhasil dihapus dari daftar.');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(static function (): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, self::CSV_HEADERS, ',', '"', '');
            fclose($output);
        }, 'template-data-siswa.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = Validator::make($request->query(), [
            'q' => 'nullable|string|max:100',
            'jurusan' => 'nullable|integer|exists:program_keahlian,id',
        ], $this->validationMessages())->validate();
        $query = $this->filteredStudents($filters)->with('programKeahlian')->orderBy('nis');
        $filename = 'data-siswa-' . now()->format('Y-m-d') . '.csv';

        Log::info('student.exported', ['user_id' => auth()->id(), 'program_keahlian_id' => $filters['jurusan'] ?? null]);

        return response()->streamDownload(static function () use ($query): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, self::CSV_HEADERS, ',', '"', '');
            foreach ($query->lazy(500) as $student) {
                fputcsv($output, [
                    self::safeCsvCell($student->nis), self::safeCsvCell($student->nama), $student->tanggal_lahir?->toDateString(),
                    self::safeCsvCell($student->programKeahlian?->nama_jurusan),
                ], ',', '"', '');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'mode' => 'required|in:tambah,perbarui',
            'file_import' => 'required|file|mimes:csv,txt|max:10240',
        ], [
            'mode.required' => 'Pilih cara impor data siswa.',
            'mode.in' => 'Cara impor data siswa tidak dikenali.',
            'file_import.required' => 'Pilih file CSV terlebih dahulu.',
            'file_import.file' => 'File yang diunggah tidak dapat dibaca.',
            'file_import.mimes' => 'File harus berformat CSV.',
            'file_import.max' => 'Ukuran file CSV maksimal 10 MB.',
        ])->validate();

        [$rows, $parseErrors] = $this->readCsv($request->file('file_import')->getRealPath());
        if ($parseErrors !== []) {
            return $this->importError($parseErrors);
        }
        if ($rows === []) {
            return $this->importError(['File CSV tidak berisi baris data.']);
        }
        if (count($rows) > 1000) {
            return $this->importError(['Maksimal 1.000 baris data per file. Pecah file menjadi beberapa bagian.']);
        }

        $records = [];
        $errors = [];
        $seen = [];
        foreach ($rows as $entry) {
            $row = array_map(static fn ($value) => $value === '' ? null : $value, $entry['data']);
            $line = $entry['line'];
            $validator = Validator::make($row, [
                'nis' => 'required|string|max:20',
                'nama' => 'required|string|max:100',
                'tanggal_lahir' => 'nullable|date_format:Y-m-d',
                'nama_jurusan' => 'required|string|max:100',
            ], [
                'required' => ':attribute wajib diisi.',
                'max' => ':attribute terlalu panjang.',
                'date_format' => ':attribute harus berformat YYYY-MM-DD.',
            ], [
                'nis' => 'NIS', 'nama' => 'nama siswa', 'tanggal_lahir' => 'tanggal lahir', 'nama_jurusan' => 'program keahlian',
            ]);
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Baris {$line}: {$message}";
                }
                continue;
            }

            $nis = $row['nis'];
            if (isset($seen[$nis])) {
                $errors[] = "Baris {$line}: NIS {$nis} muncul lebih dari sekali di dalam file.";
                continue;
            }
            $seen[$nis] = true;
            $program = ProgramKeahlian::where('nama_jurusan', $row['nama_jurusan'])->first();
            if (! $program) {
                $errors[] = "Baris {$line}: program keahlian '{$row['nama_jurusan']}' tidak ditemukan.";
                continue;
            }

            $student = Student::where('nis', $nis)->first();
            $archived = Student::onlyTrashed()->where('nis', $nis)->exists();
            if ($validated['mode'] === 'tambah' && ($student || $archived)) {
                $errors[] = "Baris {$line}: NIS {$nis} sudah terdaftar; pilih mode perbarui untuk mengubah data aktif.";
                continue;
            }
            if ($validated['mode'] === 'perbarui' && ! $student) {
                $message = $archived ? 'NIS ini berada di arsip; pulihkan siswa terlebih dahulu.' : 'NIS ini belum terdaftar.';
                $errors[] = "Baris {$line}: {$message}";
                continue;
            }

            $records[] = [
                'student' => $student,
                'data' => [
                    'nis' => $nis, 'nama' => $row['nama'], 'tanggal_lahir' => $row['tanggal_lahir'],
                    'program_keahlian_id' => $program->id,
                ],
                'line' => $line,
            ];
        }
        if ($errors !== []) {
            return $this->importError($errors);
        }

        try {
            DB::transaction(function () use ($records, $validated): void {
                foreach ($records as $record) {
                    if ($validated['mode'] === 'perbarui') {
                        $record['student']->update($record['data']);
                    } else {
                        Student::create($record['data']);
                    }
                }
            });
        } catch (QueryException) {
            return $this->importError(['NIS berubah atau bertabrakan saat penyimpanan. Tidak ada data yang disimpan; muat ulang data lalu coba lagi.']);
        }

        Log::info('student.imported', [
            'user_id' => auth()->id(), 'mode' => $validated['mode'], 'rows' => count($records),
        ]);

        return redirect()->route('siswa.index')
            ->with('success', count($records) . ' data siswa berhasil ' . ($validated['mode'] === 'tambah' ? 'ditambahkan.' : 'diperbarui.'));
    }

    private function filteredStudents(array $filters): Builder
    {
        return Student::query()
            ->when($filters['q'] ?? null, function ($query, string $keyword): void {
                $query->where(static function ($search) use ($keyword): void {
                    $search->where('nama', 'like', "%{$keyword}%")->orWhere('nis', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['jurusan'] ?? null, static fn ($query, $programId) => $query->where('program_keahlian_id', $programId));
    }

    private function readCsv(string $path): array
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
            return [[], ['Header CSV tidak valid.']];
        }
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers = array_map(static fn ($header) => trim((string) $header), $headers);
        if ($headers !== self::CSV_HEADERS) {
            fclose($handle);
            return [[], ['Kolom CSV tidak sesuai template. Unduh template data siswa yang terbaru.']];
        }

        $rows = [];
        $errors = [];
        $line = 1;
        while (($values = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;
            if (count(array_filter($values, static fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            if (count($values) !== count($headers)) {
                $errors[] = "Baris {$line}: jumlah kolom tidak sesuai header.";
                continue;
            }
            $rows[] = ['line' => $line, 'data' => array_combine($headers, array_map(static fn ($value) => trim((string) $value), $values))];
        }
        fclose($handle);

        return [$rows, $errors];
    }

    private function importError(array $errors): RedirectResponse
    {
        return redirect()->route('siswa.index')
            ->with('import_issues', array_slice($errors, 0, 30))
            ->with('error', 'Tidak ada data siswa yang diimpor. Perbaiki CSV lalu coba lagi.');
    }

    private function validationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute terlalu panjang.',
            'unique' => 'NIS tersebut sudah digunakan.',
            'date' => 'Format tanggal lahir tidak valid.',
            'date_format' => 'Tanggal lahir harus berformat YYYY-MM-DD.',
            'exists' => 'Program keahlian yang dipilih tidak ditemukan.',
        ];
    }

    private static function safeCsvCell(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return preg_match('/^[\s]*[=+@\-]/u', $value) === 1 ? "'" . $value : $value;
    }
}

@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page-title', 'Data Siswa')
@section('breadcrumb', 'PKL / Data Siswa')
@section('no-error-banner', '1')

@section('content')

<div class="card card-pad">
  <div class="card-head">
    <div>
      <h2>Data Siswa</h2>
      <div class="sub">{{ $students->total() }} siswa terdaftar — dipakai untuk penempatan PKL</div>
    </div>
    <div class="student-actions">
      <a class="btn btn-ghost btn-sm" href="{{ route('siswa.export', request()->only(['q', 'jurusan'])) }}">Ekspor CSV</a>
      @if (auth()->user()->role->name === 'Admin')<a class="btn btn-ghost btn-sm" href="{{ route('sampah.index', ['jenis' => 'siswa']) }}">Arsip siswa</a>@endif
      <button class="btn btn-primary btn-sm" onclick="openModal('modalSiswa')">+ Tambah Siswa</button>
    </div>
  </div>

  @if ($errors->any())
    <div style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:14px;">
      {{ $errors->first() }}
    </div>
  @endif

  @if (session('success'))<div class="student-alert student-alert--success" role="status">{{ session('success') }}</div>@endif
  @if (session('error'))<div class="student-alert" role="alert">{{ session('error') }}</div>@endif
  @if (session('import_issues'))
    <div class="student-alert" role="alert"><strong>Data yang perlu diperbaiki:</strong><ul>@foreach (session('import_issues') as $issue)<li>{{ $issue }}</li>@endforeach</ul></div>
  @endif

  <details class="student-transfer" @if (session('error') || session('import_issues') || $errors->has('mode') || $errors->has('file_import')) open @endif>
    <summary class="student-transfer__summary">
      <span class="student-transfer__summary-icon" aria-hidden="true">CSV</span>
      <span class="student-transfer__summary-copy"><strong>Impor data siswa</strong><small>Tambah data atau perbarui berdasarkan NIS</small></span>
      <span class="student-transfer__summary-action">Buka pengaturan <span aria-hidden="true">⌄</span></span>
    </summary>
    <div class="student-transfer__content">
      <div class="student-transfer__head">
        <div><h3 id="student-transfer-title">Unggah file CSV</h3><p>Kolom: NIS, nama, tanggal lahir, dan nama program keahlian.</p></div>
        <a href="{{ route('siswa.template') }}" class="btn btn-ghost btn-sm">Unduh template</a>
      </div>
      <form method="POST" action="{{ route('siswa.import') }}" enctype="multipart/form-data" id="studentImportForm" class="student-transfer__form">
        @csrf
        <div class="field"><label for="studentImportMode">Cara impor</label>
          <select id="studentImportMode" name="mode" required>
            <option value="tambah" @selected(old('mode', 'tambah') === 'tambah')>Tambah saja — tolak NIS yang sudah terdaftar</option>
            <option value="perbarui" @selected(old('mode') === 'perbarui')>Perbarui — cocokkan data aktif berdasarkan NIS</option>
          </select>
        </div>
        <div class="field"><label for="studentImportFile">File CSV <span>(maks. 10 MB)</span></label>
          <div class="student-file-picker" id="studentFilePicker">
            <span class="student-file-picker__icon" aria-hidden="true">CSV</span>
            <span class="student-file-picker__name" id="studentImportFileName">Belum ada file dipilih</span>
            <span class="student-file-picker__browse" aria-hidden="true">Telusuri</span>
            <input id="studentImportFile" class="student-file-picker__input" name="file_import" type="file" accept=".csv,.txt" required aria-describedby="studentFileHelp">
          </div>
          <small class="student-file-picker__help" id="studentFileHelp">Format .csv atau .txt</small>
        </div>
        <button type="submit" class="btn btn-primary" id="studentImportButton">Periksa CSV</button>
      </form>
      <div id="studentImportPreview" class="student-transfer__preview" aria-live="polite" hidden></div>
      <p class="student-transfer__note">Maksimal 1.000 baris. Tanggal gunakan YYYY-MM-DD. Jika ada baris tidak valid, seluruh file ditolak. Mode perbarui hanya mengubah siswa aktif dengan NIS yang cocok.</p>
    </div>
  </details>

  <form method="GET" action="{{ route('siswa.index') }}" class="student-search">
    <label class="student-search__query"><span aria-hidden="true">⌕</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau NIS…" aria-label="Cari nama atau NIS"></label>
    <select name="jurusan" class="student-search__select" aria-label="Filter program keahlian">
      <option value="">Semua jurusan</option>
      @foreach ($programKeahlian as $pk)
        <option value="{{ $pk->id }}" {{ request('jurusan') == $pk->id ? 'selected' : '' }}>{{ $pk->nama_jurusan }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-ghost btn-sm">Cari</button>
    @if (request()->filled('q') || request()->filled('jurusan'))<a href="{{ route('siswa.index') }}" class="student-search__reset">Bersihkan</a>@endif
  </form>

  <div class="student-table-wrap">
  <table class="student-table">
    <thead>
      <tr>
        <th>NIS</th>
        <th>Nama</th>
        <th>Program Keahlian</th>
        <th>Tgl Lahir</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($students as $s)
        <tr>
          <td class="mono">{{ $s->nis }}</td>
          <td class="cell-strong">{{ $s->nama }}</td>
          <td><span class="student-program">{{ $s->programKeahlian->nama_jurusan ?? '—' }}</span></td>
          <td>{{ $s->tanggal_lahir?->translatedFormat('d M Y') ?? '—' }}</td>
          <td style="white-space:nowrap;">
            <a class="student-row-action" href="{{ route('siswa.index', array_filter(['edit' => $s->id, 'q' => request('q'), 'jurusan' => request('jurusan')])) }}">Edit</a>
            <form method="POST" action="{{ route('siswa.destroy', $s) }}" style="display:inline;"
                  onsubmit="return confirm('Arsipkan data siswa {{ $s->nama }}? Data tetap dapat dipulihkan oleh Admin.')">
              @csrf
              @method('DELETE')
              <button type="submit" class="student-row-action student-row-action--danger">Arsipkan</button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align:center;color:var(--ink-faint);padding:28px;">
            Belum ada data siswa. Klik “+ Tambah Siswa” untuk menambah.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
  </div>

  @if ($students->hasPages())
    <div style="display:flex;justify-content:center;gap:8px;padding:16px;">
      @if ($students->onFirstPage())
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">&larr; Sebelumnya</span>
      @else
        <a href="{{ $students->previousPageUrl() }}" class="btn btn-ghost btn-sm">&larr; Sebelumnya</a>
      @endif
      <span style="font-size:12.5px;color:var(--ink-faint);align-self:center;">Hal. {{ $students->currentPage() }} / {{ $students->lastPage() }}</span>
      @if ($students->hasMorePages())
        <a href="{{ $students->nextPageUrl() }}" class="btn btn-ghost btn-sm">Berikutnya &rarr;</a>
      @else
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">Berikutnya &rarr;</span>
      @endif
    </div>
  @endif
</div>

{{-- Modal Tambah / Edit --}}
<div class="modal-overlay {{ $editing || ($errors->any() && ! request('edit') && old('nama')) ? 'open' : '' }}" id="modalSiswa">
  <div class="modal-card" style="max-width:520px;">
    <form method="POST" action="{{ $editing ? route('siswa.update', $editing) : route('siswa.store') }}">
      @csrf
      @if ($editing) @method('PUT') @endif
      <div class="modal-head">
        <h2>{{ $editing ? 'Edit Siswa' : 'Tambah Siswa' }}</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalSiswa'); window.location='{{ route('siswa.index', array_filter(['q' => request('q'), 'jurusan' => request('jurusan')])) }}';">&times;</button>
      </div>
      <div class="modal-body">
        <div class="field">
          <label>Nama Lengkap</label>
          <input type="text" name="nama" value="{{ old('nama', $editing->nama ?? '') }}" placeholder="Nama siswa" required>
        </div>
        <div class="field-row">
          <div class="field">
            <label>NIS</label>
            <input type="text" name="nis" value="{{ old('nis', $editing->nis ?? '') }}" placeholder="Nomor Induk Siswa" required>
          </div>
          <div class="field">
            <label>Tanggal Lahir <span class="hint">(opsional)</span></label>
            <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', optional($editing->tanggal_lahir ?? null)->format('Y-m-d')) }}">
          </div>
        </div>
        <div class="field">
          <label>Program Keahlian</label>
          <select name="program_keahlian_id" required>
            @foreach ($programKeahlian as $pk)
              <option value="{{ $pk->id }}" {{ old('program_keahlian_id', $editing->program_keahlian_id ?? '') == $pk->id ? 'selected' : '' }}>
                {{ $pk->nama_jurusan }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-foot">
        <a href="{{ route('siswa.index', array_filter(['q' => request('q'), 'jurusan' => request('jurusan')])) }}" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">{{ $editing ? 'Simpan Perubahan' : 'Simpan Siswa' }}</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(id){ const el=document.getElementById(id); if(el) el.classList.add('open'); }
  function closeModal(id){ const el=document.getElementById(id); if(el) el.classList.remove('open'); }
  (() => {
    const form = document.getElementById('studentImportForm');
    if (!form) return;
    const input = document.getElementById('studentImportFile');
    const button = document.getElementById('studentImportButton');
    const preview = document.getElementById('studentImportPreview');
    let previewedFile = null;
    const parseCsv = (text, delimiter) => {
      const rows = []; let row = [], cell = '', quoted = false;
      for (let i = 0; i < text.length; i++) {
        const char = text[i];
        if (quoted && char === '"' && text[i + 1] === '"') { cell += '"'; i++; }
        else if (char === '"') quoted = !quoted;
        else if (!quoted && char === delimiter) { row.push(cell); cell = ''; }
        else if (!quoted && (char === '\n' || char === '\r')) {
          if (char === '\r' && text[i + 1] === '\n') i++;
          row.push(cell); cell = ''; if (row.some(value => value.trim() !== '')) rows.push(row); row = [];
        } else cell += char;
      }
      row.push(cell); if (row.some(value => value.trim() !== '')) rows.push(row);
      return rows;
    };
    input.addEventListener('change', () => {
      previewedFile = null; preview.hidden = true; preview.replaceChildren();
      button.textContent = 'Periksa CSV'; button.disabled = false;
      document.getElementById('studentImportFileName').textContent = input.files[0]?.name || 'Belum ada file dipilih';
      document.getElementById('studentFilePicker').classList.toggle('has-file', Boolean(input.files[0]));
    });
    form.addEventListener('submit', event => {
      if (previewedFile === input.files[0] && input.files[0]) {
        const mode = document.getElementById('studentImportMode').selectedOptions[0].textContent;
        if (Number(button.dataset.rows) > 1000 || !window.confirm(`Lanjutkan impor ${Number(button.dataset.rows).toLocaleString('id-ID')} baris? Mode: ${mode}.`)) event.preventDefault();
        return;
      }
      event.preventDefault();
      const file = input.files[0]; if (!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        const text = String(reader.result || '').replace(/^\uFEFF/, '');
        const firstLine = text.split(/\r?\n/, 1)[0] || '';
        const delimiter = (firstLine.match(/;/g) || []).length > (firstLine.match(/,/g) || []).length ? ';' : ',';
        const rows = parseCsv(text, delimiter), headers = rows[0] || [], dataRows = rows.slice(1);
        if (!headers.length || !dataRows.length) { preview.hidden = false; preview.textContent = 'CSV harus memiliki header dan setidaknya satu baris data.'; return; }
        preview.replaceChildren(); preview.hidden = false;
        const summary = document.createElement('strong'); summary.textContent = `${dataRows.length} baris data terdeteksi`; preview.append(summary);
        const note = document.createElement('p'); note.textContent = 'Pratinjau lima baris pertama. Pemeriksaan NIS dan program keahlian dilakukan sebelum penyimpanan.'; preview.append(note);
        const table = document.createElement('table');
        [headers, ...dataRows.slice(0, 5)].forEach((values, rowIndex) => {
          const tr = document.createElement('tr');
          values.forEach(value => { const cell = document.createElement(rowIndex === 0 ? 'th' : 'td'); cell.textContent = value || '—'; tr.append(cell); });
          table.append(tr);
        });
        preview.append(table);
        button.textContent = dataRows.length > 1000 ? 'Maksimal 1.000 baris' : `Impor ${dataRows.length.toLocaleString('id-ID')} baris`;
        button.disabled = dataRows.length > 1000;
        button.dataset.rows = String(dataRows.length); previewedFile = file;
      };
      reader.onerror = () => { preview.hidden = false; preview.textContent = 'File tidak dapat dibaca. Pilih file CSV lain.'; };
      reader.readAsText(file);
    });
  })();
  @if ($editing)
    document.addEventListener('DOMContentLoaded', () => openModal('modalSiswa'));
  @endif
</script>
@endsection

@extends('layouts.app')

@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('breadcrumb', 'Laporan / ' . match($jenis) {
    'surat-masuk'  => 'Surat Masuk',
    'surat-keluar' => 'Surat Keluar',
    'mitra'        => 'Mitra & MoU/PKS',
    'pkl'          => 'Siswa PKL',
    'sertifikat'   => 'Sertifikat',
    default        => 'Ringkasan',
})
@section('no-error-banner', '1')

@section('content')
@php
  $tabs = [
    'ringkasan'    => 'Ringkasan',
    'surat-masuk'  => 'Surat Masuk',
    'surat-keluar' => 'Surat Keluar',
    'mitra'        => 'Mitra & MoU/PKS',
    'pkl'          => 'Siswa PKL',
    'sertifikat'   => 'Sertifikat',
  ];
  $importTypes = match ($role) {
    'Admin' => ['surat-masuk', 'surat-keluar', 'mitra', 'pkl'],
    'Koordinator TU' => ['surat-masuk', 'surat-keluar'],
    'Waka Humas' => ['mitra', 'pkl'],
    default => [],
  };
  $importLabels = ['surat-masuk' => 'Surat Masuk', 'surat-keluar' => 'Surat Keluar', 'mitra' => 'Mitra dan Dokumen Kerja Sama', 'pkl' => 'Penempatan PKL'];
@endphp

<div class="stack">

  {{-- ========== FILTER PERIODE + TAB ========== --}}
  <div class="card card-pad">
    <div class="card-head">
      <div>
        <h2>Filter Laporan</h2>
        <div class="sub">Pilih jenis laporan dan rentang tanggal</div>
      </div>
        <div class="report-export-actions">
          <button type="button" id="reportPreviewButton" class="btn btn-ghost btn-sm"
                  data-preview-url="{{ route('laporan.preview', ['jenis' => $jenis]) }}"
                  data-pdf-url="{{ route('laporan.export.pdf', ['jenis' => $jenis]) }}"
                  data-csv-url="{{ route('laporan.export', ['jenis' => $jenis]) }}">
            <span aria-hidden="true">⌕</span> Pratinjau ekspor
          </button>
          <a href="{{ route('laporan.export.pdf', ['jenis' => $jenis, 'dari' => $dari, 'sampai' => $sampai]) }}" class="btn btn-navy btn-sm"><span aria-hidden="true">↓</span> Ekspor PDF</a>
          <a href="{{ route('laporan.export', ['jenis' => $jenis, 'dari' => $dari, 'sampai' => $sampai]) }}" class="btn btn-ghost btn-sm"><span aria-hidden="true">↓</span> Ekspor CSV</a>
        </div>
    </div>
    <div class="report-export-note">PDF cocok untuk laporan hingga 2.000 baris. Gunakan CSV untuk data lebih banyak.</div>
    @if ($errors->has('pdf') || $errors->has('jenis') || $errors->has('dari') || $errors->has('sampai'))
      <div role="alert" class="report-import__error">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('laporan.index') }}" id="formLaporan">
      <input type="hidden" name="jenis" id="inputJenis" value="{{ $jenis }}">

      <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;margin-bottom:16px;">
        <div style="flex:1;min-width:140px;">
          <label style="display:block;font-size:12px;color:var(--ink-soft);margin-bottom:4px;font-weight:600;">Dari tanggal</label>
          <input type="date" name="dari" value="{{ $dari }}"
                 style="width:100%;border:1px solid var(--border-strong);border-radius:var(--radius-control);padding:8px 12px;font:inherit;font-size:13px;background:var(--surface-sunken);">
        </div>
        <div style="flex:1;min-width:140px;">
          <label style="display:block;font-size:12px;color:var(--ink-soft);margin-bottom:4px;font-weight:600;">Sampai tanggal</label>
          <input type="date" name="sampai" value="{{ $sampai }}"
                 style="width:100%;border:1px solid var(--border-strong);border-radius:var(--radius-control);padding:8px 12px;font:inherit;font-size:13px;background:var(--surface-sunken);">
        </div>
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
          <a href="{{ route('laporan.index', ['jenis' => $jenis]) }}" class="btn btn-ghost btn-sm">Reset bulan ini</a>
        </div>
      </div>

      <div class="filter-bar">
        @foreach ($tabs as $key => $label)
          <button type="button"
                  class="chip {{ $jenis === $key ? 'active' : '' }}"
                  onclick="document.getElementById('inputJenis').value='{{ $key }}'; document.getElementById('formLaporan').submit();">
            {{ $label }}
          </button>
        @endforeach
      </div>
    </form>
  </div>

  @if ($importTypes !== [])
    <section class="card card-pad report-import" aria-labelledby="report-import-title">
      <div class="report-import__header">
        <div>
          <div class="report-import__eyebrow">KELOLA DATA</div>
          <h2 id="report-import-title">Impor data dari CSV</h2>
          <p>Tambahkan data dalam jumlah banyak. Data yang sudah ada tidak akan ditimpa.</p>
        </div>
        <span class="report-import__limit">CSV <span>·</span> Maks. 10 MB</span>
      </div>

      @if (session('error'))<div role="alert" class="report-import__error">{{ session('error') }}</div>@endif
      @if ($errors->has('jenis_import') || $errors->has('file_import'))<div role="alert" class="report-import__error">{{ $errors->first() }}</div>@endif
      @if (session('import_issues'))
        <div class="report-import__error">
          <strong>Baris yang perlu diperbaiki:</strong>
          <ul>@foreach (session('import_issues') as $issue)<li>{{ $issue }}</li>@endforeach</ul>
        </div>
      @endif

      <div class="report-import__grid">
        <section class="report-import__templates" aria-labelledby="report-template-title">
          <div class="report-import__section-head">
            <div><span class="report-import__step">01</span><h3 id="report-template-title">Unduh template</h3></div>
            <span>Mulai dari format yang sesuai</span>
          </div>
          <div class="report-template-grid">
            @foreach ($importTypes as $type)
              <a class="report-template" href="{{ route('laporan.template', $type) }}" download="template-impor-{{ $type }}.csv">
                <span class="report-template__icon" aria-hidden="true">CSV</span>
                <span class="report-template__copy"><strong>{{ $importLabels[$type] }}</strong><small>Template data {{ strtolower($importLabels[$type]) }}</small></span>
                <span class="report-template__download" aria-hidden="true">↓</span>
              </a>
            @endforeach
          </div>
        </section>

        <section class="report-import__upload" aria-labelledby="report-upload-title">
          <div class="report-import__section-head">
            <div><span class="report-import__step">02</span><h3 id="report-upload-title">Unggah data</h3></div>
            <span>Maks. 1.000 baris</span>
          </div>
          <form method="POST" action="{{ route('laporan.import') }}" enctype="multipart/form-data" class="report-import__form" id="reportImportForm">
            @csrf
            <div class="field">
              <label for="jenis_import">Jenis data</label>
              <select id="jenis_import" name="jenis_import" required>
                @foreach ($importTypes as $type)<option value="{{ $type }}" @selected(old('jenis_import', in_array($jenis, $importTypes, true) ? $jenis : $importTypes[0]) === $type)>{{ $importLabels[$type] }}</option>@endforeach
              </select>
            </div>
            <div class="field">
              <label for="file_import">Pilih file CSV</label>
              <input id="file_import" type="file" name="file_import" accept=".csv,.txt" required>
            </div>
            <button type="submit" class="btn btn-primary report-import__submit" id="reportImportSubmit"><span aria-hidden="true">↑</span> Periksa CSV</button>
          </form>
          <div class="report-import__preview" id="reportImportPreview" aria-live="polite" hidden></div>
        </section>
      </div>

      <div class="report-import__guidance">
        <span class="report-import__info-icon" aria-hidden="true">i</span>
        <div><strong>Sebelum mengimpor</strong><p>Isi tanggal dengan format YYYY-MM-DD dan pertahankan nama kolom template. Pratinjau menampilkan jumlah baris dan lima baris pertama; pemeriksaan lengkap dilakukan saat impor. Jika ada satu baris tidak valid, seluruh file ditolak. Surat keluar dibuat sebagai draf; impor mitra membuat pengajuan dengan alur persetujuan.</p></div>
      </div>
    </section>
  @endif

  {{-- ========== RINGKASAN ========== --}}
  @if ($jenis === 'ringkasan')
    <div class="grid-4">
      <div class="stat">
        <div class="label">Surat Masuk</div>
        <div class="value">{{ $suratMasuk }}</div>
        <div class="delta" style="color:var(--ink-faint);font-size:11.5px;">periode terpilih</div>
      </div>
      <div class="stat">
        <div class="label">Surat Keluar</div>
        <div class="value">{{ $suratKeluar }}</div>
        <div class="delta" style="color:var(--ink-faint);font-size:11.5px;">periode terpilih</div>
      </div>
      <div class="stat">
        <div class="label">Mitra Aktif</div>
        <div class="value">{{ $mitraAktif }}</div>
        <div class="delta" style="color:var(--ink-faint);font-size:11.5px;">{{ $dokumenAktif }} dokumen berlaku</div>
      </div>
      <div class="stat">
        <div class="label">Sertifikat Terbit</div>
        <div class="value">{{ $sertifikat }}</div>
        <div class="delta" style="color:var(--ink-faint);font-size:11.5px;">periode terpilih</div>
      </div>
    </div>

    <div class="grid-2 report-summary-grid">
      <div class="card card-pad">
        <div class="card-head"><h2>Surat Keluar per Status</h2></div>
        <table class="report-status-table">
          <thead><tr><th>Status</th><th style="text-align:right;">Jumlah</th></tr></thead>
          <tbody>
            @forelse ($keluarByStatus as $st => $jml)
              <tr>
                <td>
                  @php
                    $pill = match($st) {
                      'terkirim' => 'pill-success',
                      'menunggu_ttd' => 'pill-warning',
                      default => 'pill-neutral',
                    };
                    $labelSt = match($st) {
                      'draf' => 'Draf',
                      'menunggu_ttd' => 'Menunggu TTD',
                      'terkirim' => 'Terkirim',
                      default => $st,
                    };
                  @endphp
                  <span class="pill {{ $pill }}"><span class="dot"></span>{{ $labelSt }}</span>
                </td>
                <td style="text-align:right;" class="cell-strong">{{ $jml }}</td>
              </tr>
            @empty
              <tr><td colspan="2" style="text-align:center;color:var(--ink-faint);padding:20px;">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="card card-pad">
        <div class="card-head"><h2>Siswa PKL per Status</h2></div>
        <table class="report-status-table">
          <thead><tr><th>Status</th><th style="text-align:right;">Jumlah</th></tr></thead>
          <tbody>
            @forelse ($pklByStatus as $st => $jml)
              <tr>
                <td>
                  @php
                    $pill = match($st) {
                      'berjalan' => 'pill-success',
                      'selesai' => 'pill-neutral',
                      'dibatalkan' => 'pill-danger',
                      default => 'pill-warning',
                    };
                    $labelPkl = match($st) { 'berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan', default => $st };
                  @endphp
                  <span class="pill {{ $pill }}"><span class="dot"></span>{{ $labelPkl }}</span>
                </td>
                <td style="text-align:right;" class="cell-strong">{{ $jml }}</td>
              </tr>
            @empty
              <tr><td colspan="2" style="text-align:center;color:var(--ink-faint);padding:20px;">Tidak ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
        <div style="margin-top:12px;font-size:12.5px;color:var(--ink-soft);">
          PKL selesai di periode ini: <strong>{{ $pklSelesai }}</strong> &middot; Sedang aktif: <strong>{{ $pklAktif }}</strong>
        </div>
      </div>
    </div>
  @endif

  {{-- ========== SURAT MASUK ========== --}}
  @if ($jenis === 'surat-masuk')
    <div class="grid-4">
      <div class="stat"><div class="label">Total</div><div class="value">{{ $total }}</div></div>
      <div class="stat"><div class="label">Menunggu disposisi</div><div class="value">{{ $menunggu }}</div></div>
      <div class="stat"><div class="label">Diproses</div><div class="value">{{ $diproses }}</div></div>
      <div class="stat"><div class="label">Selesai</div><div class="value">{{ $selesai }}</div></div>
    </div>

    <div class="card card-pad">
      <div class="card-head">
        <div><h2>Daftar Surat Masuk</h2><div class="sub">{{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</div></div>
      </div>
      <table>
        <thead>
          <tr>
            <th>No. Agenda</th>
            <th>Pengirim</th>
            <th>Perihal</th>
            <th>Tgl Terima</th>
            <th>Sifat</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($items as $s)
            @php
              $belumAda = $s->dispositions->isEmpty();
              $semuaSelesai = ! $belumAda && $s->dispositions->every(fn($d) => $d->status === 'selesai');
              $statusLabel = $belumAda ? 'Menunggu' : ($semuaSelesai ? 'Selesai' : 'Diproses');
              $pillClass = $statusLabel === 'Selesai' ? 'pill-success' : ($statusLabel === 'Menunggu' ? 'pill-warning' : 'pill-neutral');
            @endphp
            <tr>
              <td class="mono">{{ $s->no_agenda }}</td>
              <td class="cell-strong">{{ $s->pengirim }}</td>
              <td>{{ $s->perihal }}</td>
              <td>{{ $s->tanggal_terima?->translatedFormat('d M Y') }}</td>
              <td>{{ $s->sifat ?? '—' }}</td>
              <td><span class="pill {{ $pillClass }}"><span class="dot"></span>{{ $statusLabel }}</span></td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align:center;color:var(--ink-faint);padding:28px;">Tidak ada surat masuk pada periode ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif

  {{-- ========== SURAT KELUAR ========== --}}
  @if ($jenis === 'surat-keluar')
    <div class="grid-4">
      <div class="stat"><div class="label">Total</div><div class="value">{{ $total }}</div></div>
      <div class="stat"><div class="label">Draf</div><div class="value">{{ $byStatus['draf'] ?? 0 }}</div></div>
      <div class="stat"><div class="label">Menunggu TTD</div><div class="value">{{ $byStatus['menunggu_ttd'] ?? 0 }}</div></div>
      <div class="stat"><div class="label">Terkirim</div><div class="value">{{ $byStatus['terkirim'] ?? 0 }}</div></div>
    </div>

    @if ($byJenis->isNotEmpty())
      <div class="card card-pad">
        <div class="card-head"><h2>Per Jenis Template</h2></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
          @foreach ($byJenis as $jenisTpl => $jml)
            <div class="stat" style="min-width:120px;flex:0;">
              <div class="label">{{ str_replace('_', ' ', ucfirst($jenisTpl ?? 'umum')) }}</div>
              <div class="value" style="font-size:22px;">{{ $jml }}</div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    <div class="card card-pad">
      <div class="card-head">
        <div><h2>Daftar Surat Keluar</h2><div class="sub">{{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</div></div>
      </div>
      <table>
        <thead>
          <tr>
            <th>No. Surat</th>
            <th>Tujuan</th>
            <th>Perihal</th>
            <th>Tanggal</th>
            <th>Jenis</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($items as $s)
            @php
              $pill = match($s->status) {
                'terkirim' => 'pill-success',
                'menunggu_ttd' => 'pill-warning',
                default => 'pill-neutral',
              };
              $labelSt = match($s->status) {
                'draf' => 'Draf',
                'menunggu_ttd' => 'Menunggu TTD',
                'terkirim' => 'Terkirim',
                default => $s->status,
              };
            @endphp
            <tr>
              <td class="mono">{{ $s->no_surat }}</td>
              <td class="cell-strong">{{ $s->tujuan }}</td>
              <td>{{ $s->perihal }}</td>
              <td>{{ $s->tanggal?->translatedFormat('d M Y') }}</td>
              <td>{{ str_replace('_', ' ', ucfirst($s->jenis_template ?? 'umum')) }}</td>
              <td><span class="pill {{ $pill }}"><span class="dot"></span>{{ $labelSt }}</span></td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align:center;color:var(--ink-faint);padding:28px;">Tidak ada surat keluar pada periode ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif

  {{-- ========== MITRA ========== --}}
  @if ($jenis === 'mitra')
    <div class="grid-4">
      <div class="stat"><div class="label">Total Mitra</div><div class="value">{{ $totalMitra }}</div></div>
      <div class="stat"><div class="label">Mitra Aktif</div><div class="value">{{ $mitraAktif }}</div></div>
      <div class="stat"><div class="label">Dok. Disetujui</div><div class="value">{{ $dokumenByStatus['disetujui'] ?? 0 }}</div></div>
      <div class="stat"><div class="label">Akan Kadaluarsa (60 hr)</div><div class="value">{{ $akanKadaluarsa->count() }}</div></div>
    </div>

    @if ($akanKadaluarsa->isNotEmpty())
      <div class="card card-pad">
        <div class="card-head">
          <div><h2>Dokumen Akan Kadaluarsa</h2><div class="sub">Berlaku selesai dalam 60 hari ke depan</div></div>
        </div>
        <table>
          <thead><tr><th>Mitra</th><th>Jenis</th><th>Ruang Lingkup</th><th>Berlaku Selesai</th></tr></thead>
          <tbody>
            @foreach ($akanKadaluarsa as $d)
              <tr>
                <td class="cell-strong">{{ $d->partner->nama_perusahaan ?? '—' }}</td>
                <td>{{ $d->jenis_dokumen }}@if($d->nomor_dokumen) <span class="mono" style="font-size:11px;">({{ $d->nomor_dokumen }})</span>@endif</td>
                <td>{{ $d->ruang_lingkup ?? '—' }}</td>
                <td><span class="pill pill-warning"><span class="dot"></span>{{ $d->berlaku_selesai?->translatedFormat('d M Y') }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

    <div class="card card-pad">
      <div class="card-head">
        <div><h2>Dokumen Kerja Sama (periode)</h2><div class="sub">Berdasarkan tanggal mulai / tanggal dibuat</div></div>
      </div>
      <table>
        <thead>
          <tr>
            <th>Mitra</th>
            <th>Jenis</th>
            <th>No. Dokumen</th>
            <th>Ruang Lingkup</th>
            <th>Berlaku</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($documents as $d)
            @php
              $pill = match($d->status) {
                'disetujui' => 'pill-success',
                'ditolak', 'kadaluarsa' => 'pill-danger',
                'diajukan', 'diverifikasi' => 'pill-warning',
                default => 'pill-neutral',
              };
            @endphp
            <tr>
              <td class="cell-strong">{{ $d->partner->nama_perusahaan ?? '—' }}</td>
              <td>{{ $d->jenis_dokumen }}</td>
              <td class="mono">{{ $d->nomor_dokumen ?? '—' }}</td>
              <td>{{ $d->ruang_lingkup ?? '—' }}</td>
              <td class="mono" style="font-size:12px;">
                {{ $d->berlaku_mulai?->translatedFormat('d M Y') }}
                – {{ $d->berlaku_selesai?->translatedFormat('d M Y') }}
              </td>
              <td><span class="pill {{ $pill }}"><span class="dot"></span>{{ ucfirst($d->status) }}</span></td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align:center;color:var(--ink-faint);padding:28px;">Tidak ada dokumen pada periode ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif

  {{-- ========== PKL ========== --}}
  @if ($jenis === 'pkl')
    <div class="grid-4">
      <div class="stat"><div class="label">Total (periode)</div><div class="value">{{ $total }}</div></div>
      <div class="stat"><div class="label">Berjalan</div><div class="value">{{ $byStatus['berjalan'] ?? 0 }}</div></div>
      <div class="stat"><div class="label">Selesai</div><div class="value">{{ $byStatus['selesai'] ?? 0 }}</div></div>
      <div class="stat"><div class="label">Dibatalkan</div><div class="value">{{ $byStatus['dibatalkan'] ?? 0 }}</div></div>
    </div>
    <div class="hint" style="margin-top:-4px;">Total siswa terdaftar: <strong>{{ $siswaTotal }}</strong></div>

    @if ($byJurusan->isNotEmpty())
      <div class="card card-pad">
        <div class="card-head"><h2>Per Program Keahlian</h2></div>
        <table>
          <thead><tr><th>Program Keahlian</th><th style="text-align:right;">Jumlah</th></tr></thead>
          <tbody>
            @foreach ($byJurusan as $jurusan => $jml)
              <tr>
                <td class="cell-strong">{{ $jurusan }}</td>
                <td style="text-align:right;">{{ $jml }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

    <div class="card card-pad">
      <div class="card-head">
        <div><h2>Daftar Penempatan PKL</h2><div class="sub">{{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</div></div>
      </div>
      <table>
        <thead>
          <tr>
            <th>Siswa</th>
            <th>Industri</th>
            <th>Periode</th>
            <th>Pembimbing</th>
            <th>Status</th>
            <th>Nilai</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($items as $p)
            @php
              $pill = match($p->status) {
                'berjalan' => 'pill-success',
                'selesai' => 'pill-neutral',
                'dibatalkan' => 'pill-danger',
                default => 'pill-warning',
              };
            @endphp
            <tr>
              <td class="cell-strong">
                {{ $p->student->nama ?? '—' }}
                <div class="cell-sub">NIS {{ $p->student->nis ?? '—' }} &middot; {{ $p->student->programKeahlian->nama_jurusan ?? '—' }}</div>
              </td>
              <td>{{ $p->partner->nama_perusahaan ?? '—' }}</td>
              <td class="mono" style="font-size:12px;">{{ $p->periodeText() }}</td>
              <td>{{ $p->pembimbing->name ?? '—' }}</td>
              <td><span class="pill {{ $pill }}"><span class="dot"></span>{{ ucfirst($p->status) }}</span></td>
              <td>{{ $p->nilai !== null ? number_format($p->nilai, 1) : '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align:center;color:var(--ink-faint);padding:28px;">Tidak ada data PKL pada periode ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif

  {{-- ========== SERTIFIKAT ========== --}}
  @if ($jenis === 'sertifikat')
    <div class="grid-4">
      <div class="stat"><div class="label">Total Terbit</div><div class="value">{{ $total }}</div></div>
    </div>

    <div class="card card-pad">
      <div class="card-head">
        <div><h2>Daftar Sertifikat</h2><div class="sub">{{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</div></div>
      </div>
      <table>
        <thead>
          <tr>
            <th>No. Sertifikat</th>
            <th>Siswa</th>
            <th>Industri</th>
            <th>Tgl Terbit</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($items as $c)
            <tr>
              <td class="mono">{{ $c->nomor_sertifikat }}</td>
              <td class="cell-strong">
                {{ $c->placement->student->nama ?? '—' }}
                <div class="cell-sub">{{ $c->placement->student->programKeahlian->nama_jurusan ?? '—' }}</div>
              </td>
              <td>{{ $c->placement->partner->nama_perusahaan ?? '—' }}</td>
              <td>{{ $c->tanggal_terbit?->translatedFormat('d M Y') }}</td>
            </tr>
          @empty
            <tr><td colspan="4" style="text-align:center;color:var(--ink-faint);padding:28px;">Tidak ada sertifikat pada periode ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif

</div>

<div class="report-preview" id="reportPreviewModal" aria-hidden="true">
  <section class="report-preview__dialog" role="dialog" aria-modal="true" aria-labelledby="reportPreviewTitle">
    <header class="report-preview__header">
      <div><span class="report-preview__eyebrow">PRATINJAU EKSPOR</span><h2 id="reportPreviewTitle">Pratinjau laporan</h2></div>
      <button type="button" class="report-preview__close" id="reportPreviewClose" aria-label="Tutup pratinjau">&times;</button>
    </header>
    <div class="report-preview__body" id="reportPreviewBody"><p>Memuat pratinjau…</p></div>
    <footer class="report-preview__footer">
      <span id="reportPreviewNote">Pratinjau mengikuti filter laporan yang dipilih.</span>
      <div><a id="reportPreviewPdf" href="#" class="btn btn-navy btn-sm">Unduh PDF</a><a id="reportPreviewCsv" href="#" class="btn btn-ghost btn-sm">Unduh CSV</a></div>
    </footer>
  </section>
</div>
@endsection

@section('scripts')
<script>
(() => {
  const trigger = document.getElementById('reportPreviewButton');
  const modal = document.getElementById('reportPreviewModal');
  if (!trigger || !modal) return;
  const body = document.getElementById('reportPreviewBody');
  const note = document.getElementById('reportPreviewNote');
  const pdfLink = document.getElementById('reportPreviewPdf');
  const csvLink = document.getElementById('reportPreviewCsv');
  const close = () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); };
  document.getElementById('reportPreviewClose').addEventListener('click', close);
  modal.addEventListener('click', event => { if (event.target === modal) close(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && modal.classList.contains('open')) close(); });
  trigger.addEventListener('click', async () => {
    const filters = document.getElementById('formLaporan');
    const params = new URLSearchParams({ dari: filters.elements.dari.value, sampai: filters.elements.sampai.value });
    const appendFilters = base => `${base}?${params.toString()}`;
    pdfLink.href = appendFilters(trigger.dataset.pdfUrl);
    csvLink.href = appendFilters(trigger.dataset.csvUrl);
    modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false');
    body.replaceChildren();
    const loading = document.createElement('p'); loading.textContent = 'Memuat pratinjau…'; body.append(loading);
    try {
      const response = await fetch(appendFilters(trigger.dataset.previewUrl), { headers: { Accept: 'application/json' } });
      if (!response.ok) throw new Error('Pratinjau gagal dimuat. Periksa rentang tanggal lalu coba lagi.');
      const data = await response.json();
      document.getElementById('reportPreviewTitle').textContent = data.title;
      body.replaceChildren();
      const count = document.createElement('p');
      count.className = 'report-preview__count';
      count.textContent = data.rows.length ? `Menampilkan ${data.rows.length} baris pertama.` : 'Tidak ada data pada filter ini.';
      body.append(count);
      if (data.headers.length) {
        const wrapper = document.createElement('div'); wrapper.className = 'report-preview__table-wrap';
        const table = document.createElement('table');
        const head = document.createElement('thead'), headerRow = document.createElement('tr');
        data.headers.forEach(value => { const cell = document.createElement('th'); cell.textContent = String(value).replaceAll('_', ' '); headerRow.append(cell); });
        head.append(headerRow); table.append(head);
        const tbody = document.createElement('tbody');
        data.rows.forEach(row => {
          const tr = document.createElement('tr');
          row.forEach(value => { const cell = document.createElement('td'); cell.textContent = value || '—'; tr.append(cell); });
          tbody.append(tr);
        });
        table.append(tbody); wrapper.append(table); body.append(wrapper);
      }
      note.textContent = data.has_more ? `Menampilkan ${data.limit} baris pertama. File ekspor memuat seluruh data pada filter terpilih.` : 'Pratinjau menampilkan seluruh data yang cocok dengan filter.';
    } catch (error) {
      body.replaceChildren();
      const message = document.createElement('p'); message.className = 'report-preview__error';
      message.textContent = error.message || 'Pratinjau gagal dimuat.'; body.append(message);
    }
  });
})();

(() => {
  const form = document.getElementById('reportImportForm');
  if (!form) return;
  const input = document.getElementById('file_import');
  const button = document.getElementById('reportImportSubmit');
  const preview = document.getElementById('reportImportPreview');
  let previewedFile = null;

  const parseCsv = (text, delimiter) => {
    const rows = [];
    let row = [], cell = '', quoted = false;
    for (let i = 0; i < text.length; i++) {
      const char = text[i];
      if (quoted && char === '"' && text[i + 1] === '"') { cell += '"'; i++; }
      else if (char === '"') quoted = !quoted;
      else if (!quoted && char === delimiter) { row.push(cell); cell = ''; }
      else if (!quoted && (char === '\n' || char === '\r')) {
        if (char === '\r' && text[i + 1] === '\n') i++;
        row.push(cell); cell = '';
        if (row.some(value => value.trim() !== '')) rows.push(row);
        row = [];
      } else cell += char;
    }
    row.push(cell);
    if (row.some(value => value.trim() !== '')) rows.push(row);
    return rows;
  };

  const renderPreview = (rows, count) => {
    preview.replaceChildren();
    preview.hidden = false;
    const summary = document.createElement('strong');
    summary.textContent = `${count} baris data terdeteksi`;
    preview.append(summary);
    const note = document.createElement('p');
    note.textContent = 'Periksa header dan contoh isi berikut. Validasi referensi serta format dilakukan di server sebelum data disimpan.';
    preview.append(note);
    const table = document.createElement('table');
    table.className = 'report-import__preview-table';
    rows.slice(0, 6).forEach((row, rowIndex) => {
      const tr = document.createElement('tr');
      row.forEach(value => {
        const cell = document.createElement(rowIndex === 0 ? 'th' : 'td');
        cell.textContent = value || '—';
        tr.append(cell);
      });
      table.append(tr);
    });
    preview.append(table);
    button.innerHTML = '<span aria-hidden="true">↑</span> Impor ' + count.toLocaleString('id-ID') + ' baris';
    button.disabled = count > 1000;
    if (count > 1000) {
      const limit = document.createElement('p');
      limit.className = 'report-import__preview-limit';
      limit.textContent = 'Batas impor adalah 1.000 baris. Bagi file menjadi beberapa bagian sebelum melanjutkan.';
      preview.append(limit);
    }
    button.dataset.rowCount = String(count);
    previewedFile = input.files[0];
  };

  input.addEventListener('change', () => {
    previewedFile = null;
    preview.hidden = true;
    preview.replaceChildren();
    button.innerHTML = '<span aria-hidden="true">↑</span> Periksa CSV';
    button.disabled = false;
  });

  form.addEventListener('submit', event => {
    if (previewedFile === input.files[0] && input.files[0]) {
      if (Number(button.dataset.rowCount) > 1000 || !window.confirm(`Impor ${Number(button.dataset.rowCount).toLocaleString('id-ID')} baris? Data akan ditambahkan sebagai data baru dan tidak memperbarui data yang ada.`)) event.preventDefault();
      return;
    }
    event.preventDefault();
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      const text = String(reader.result || '').replace(/^\uFEFF/, '');
      const firstLine = text.split(/\r?\n/, 1)[0] || '';
      const delimiter = (firstLine.match(/;/g) || []).length > (firstLine.match(/,/g) || []).length ? ';' : ',';
      const rows = parseCsv(text, delimiter);
      const headers = rows[0] || [];
      const dataRows = rows.slice(1);
      if (!headers.length || !dataRows.length) {
        preview.hidden = false;
        preview.textContent = 'CSV belum memiliki header dan baris data. Gunakan template yang sesuai lalu isi datanya.';
        return;
      }
      renderPreview([headers, ...dataRows.slice(0, 5)], dataRows.length);
    };
    reader.onerror = () => {
      preview.hidden = false;
      preview.textContent = 'File tidak dapat dibaca. Coba pilih file CSV lain.';
    };
    reader.readAsText(file);
  });
})();
</script>
@endsection

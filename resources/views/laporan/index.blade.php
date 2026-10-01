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
@endphp

<div class="stack">

  {{-- ========== FILTER PERIODE + TAB ========== --}}
  <div class="card card-pad">
    <div class="card-head">
      <div>
        <h2>Filter Laporan</h2>
        <div class="sub">Pilih jenis laporan dan rentang tanggal</div>
      </div>
    </div>

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

    <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="card card-pad">
        <div class="card-head"><h2>Surat Keluar per Status</h2></div>
        <table>
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
        <table>
          <thead><tr><th>Status</th><th style="text-align:right;">Jumlah</th></tr></thead>
          <tbody>
            @forelse ($pklByStatus as $st => $jml)
              <tr>
                <td>
                  @php
                    $pill = match($st) {
                      'aktif' => 'pill-success',
                      'selesai' => 'pill-neutral',
                      'batal' => 'pill-danger',
                      default => 'pill-warning',
                    };
                  @endphp
                  <span class="pill {{ $pill }}"><span class="dot"></span>{{ ucfirst($st) }}</span>
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
      <div class="stat"><div class="label">Aktif</div><div class="value">{{ $byStatus['aktif'] ?? 0 }}</div></div>
      <div class="stat"><div class="label">Selesai</div><div class="value">{{ $byStatus['selesai'] ?? 0 }}</div></div>
      <div class="stat"><div class="label">Total Siswa Terdaftar</div><div class="value">{{ $siswaTotal }}</div></div>
    </div>

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
                'aktif' => 'pill-success',
                'selesai' => 'pill-neutral',
                'batal' => 'pill-danger',
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
@endsection

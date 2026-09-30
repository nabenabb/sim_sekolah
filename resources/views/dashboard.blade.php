@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumb', $role . ' / Beranda')

@section('content')

<div class="stack">

  <div class="card card-pad">
    <div class="card-head"><div><h2>Selamat datang, {{ auth()->user()->name }}</h2><div class="sub">Anda masuk sebagai {{ $role }}</div></div></div>
    <p style="font-size:14px;color:var(--ink-soft);margin:0;">Pilih menu di sidebar sebelah kiri untuk mulai bekerja.</p>
  </div>

  {{-- ================= ADMIN: ringkasan sistem ================= --}}
  @if ($role === 'Admin')
    <div class="grid-4">
      <div class="stat"><div class="label">User aktif</div><div class="value">{{ $adminStats['userAktif'] }}</div></div>
      <div class="stat"><div class="label">Surat masuk bulan ini</div><div class="value">{{ $adminStats['suratBulanIni'] }}</div></div>
      <div class="stat"><div class="label">Mitra industri aktif</div><div class="value">{{ $adminStats['mitraAktif'] }}</div></div>
      <div class="stat"><div class="label">Siswa PKL aktif</div><div class="value">{{ $adminStats['siswaPklAktif'] }}</div></div>
    </div>

    <div class="card card-pad">
      <div class="card-head"><h2>Akses cepat</h2></div>
      <div class="grid-4">
        <a class="btn btn-ghost" href="{{ route('manajemen-pengguna.index') }}">Manajemen Pengguna</a>
        <a class="btn btn-ghost" href="{{ route('surat-masuk.index') }}">Surat Masuk</a>
        <a class="btn btn-ghost" href="{{ route('mitra-industri.index') }}">Kerja Sama Industri</a>
        <a class="btn btn-ghost" href="{{ route('pkl.index') }}">Data Siswa PKL</a>
      </div>
    </div>
  @endif

  {{-- ================= KEPALA PROGRAM KEAHLIAN: menunggu verifikasi ================= --}}
  @if ($role === 'Kepala Program Keahlian')
    <div class="card card-pad">
      <div class="card-head">
        <div><h2>Menunggu Verifikasi Anda</h2><div class="sub">Pengajuan kerja sama yang sudah lolos tahap Humas</div></div>
      </div>
      <table>
        <thead><tr><th>Mitra</th><th>Jenis</th><th>Diajukan</th><th></th></tr></thead>
        <tbody>
          @forelse ($menungguKaprog as $doc)
            <tr>
              <td class="cell-strong">{{ $doc->partner->nama_perusahaan }}</td>
              <td>{{ $doc->jenis_dokumen }}</td>
              <td>{{ $doc->created_at->translatedFormat('d M Y') }}</td>
              <td><a class="link-more" href="{{ route('mitra-industri.index') }}">Tinjau &rarr;</a></td>
            </tr>
          @empty
            <tr><td colspan="4" style="text-align:center;color:var(--ink-faint);padding:24px;">Tidak ada pengajuan yang menunggu verifikasi Anda.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif

  {{-- ================= GURU: siswa bimbingan PKL ================= --}}
  @if ($role === 'Guru')
    <div class="card card-pad">
      <div class="card-head">
        <div><h2>Siswa Bimbingan PKL Aktif</h2><div class="sub">Siswa yang sedang Anda bimbing selama PKL</div></div>
      </div>
      <table>
        <thead><tr><th>Nama Siswa</th><th>Industri</th><th>Periode</th></tr></thead>
        <tbody>
          @forelse ($bimbinganAktif as $p)
            <tr>
              <td class="cell-strong">{{ $p->student->nama }}<div class="cell-sub">NIS {{ $p->student->nis }} &middot; {{ $p->student->programKeahlian->nama_jurusan ?? '—' }}</div></td>
              <td>{{ $p->partner->nama_perusahaan }}</td>
              <td class="mono">{{ $p->periodeText() }}</td>
            </tr>
          @empty
            <tr><td colspan="3" style="text-align:center;color:var(--ink-faint);padding:24px;">Anda belum membimbing siswa PKL yang sedang aktif.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif

  {{-- ================= PENERIMA DISPOSISI (Waka Kurikulum/Kesiswaan/Sapras) ================= --}}
  @if (isset($disposisiList))
    <div class="card card-pad">
      <div class="card-head">
        <div><h2>Disposisi untuk Anda</h2><div class="sub">{{ $disposisiList->count() }} disposisi belum selesai</div></div>
        <a class="link-more" href="{{ route('disposisi-saya.index') }}">Lihat semua &rarr;</a>
      </div>
      <table>
        <thead><tr><th>Perihal</th><th>Instruksi</th><th>Status</th></tr></thead>
        <tbody>
          @forelse ($disposisiList as $d)
            <tr>
              <td class="cell-strong">{{ $d->letter->perihal }}<div class="cell-sub">{{ $d->letter->pengirim }}</div></td>
              <td>{{ $d->instruksi }}</td>
              <td>
                <span class="pill {{ $d->status === 'diproses' ? 'pill-warning' : 'pill-neutral' }}"><span class="dot"></span>{{ ucfirst($d->status) }}</span>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" style="text-align:center;color:var(--ink-faint);padding:24px;">Tidak ada disposisi yang menunggu tindak lanjut.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif

  @if (! isset($adminStats) && ! isset($menungguKaprog) && ! isset($bimbinganAktif) && ! isset($disposisiList))
    <div class="card card-pad">
      <p style="font-size:13.5px;color:var(--ink-faint);margin:0;">Belum ada ringkasan khusus untuk role ini. Gunakan menu di sidebar untuk mengakses fitur yang tersedia.</p>
    </div>
  @endif

</div>
@endsection

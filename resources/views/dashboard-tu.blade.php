@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumb', 'Tata Usaha / Beranda')

@section('content')
<div class="stack">
  <div class="grid-3">
    <div class="stat">
      <div class="icon-box" style="background:var(--primary-tint);color:var(--primary);"><span class="ic">&#9636;</span></div>
      <div class="label">Surat masuk bulan ini</div>
      <div class="value">{{ $masukIni }}</div>
      <div class="delta {{ $deltaMasuk['class'] }}">{{ $deltaMasuk['text'] }}</div>
    </div>
    <div class="stat">
      <div class="icon-box" style="background:var(--accent-tint);color:var(--warning);"><span class="ic">&#9201;</span></div>
      <div class="label">Disposisi tertunda</div>
      <div class="value">{{ $disposisiTertunda }}</div>
      <div class="delta warn">Perlu ditindaklanjuti</div>
    </div>
    <div class="stat">
      <div class="icon-box" style="background:var(--success-tint);color:var(--success);"><span class="ic">&#9635;</span></div>
      <div class="label">Surat keluar bulan ini</div>
      <div class="value">{{ $keluarIni }}</div>
      <div class="delta {{ $deltaKeluar['class'] }}">{{ $deltaKeluar['text'] }}</div>
    </div>
  </div>

  <div class="grid-2">
    <div class="card card-pad">
      <div class="card-head">
        <div><h2>Surat masuk terbaru</h2><div class="sub">10 surat terakhir yang tercatat di agenda digital</div></div>
        <a class="link-more" href="{{ route('surat-masuk.index') }}">Lihat semua &rarr;</a>
      </div>
      <table>
        <thead><tr><th>No. Agenda</th><th>Pengirim</th><th>Perihal</th><th>Tanggal</th><th>Status</th></tr></thead>
        <tbody>
          @forelse ($suratTerbaru as $s)
            <tr>
              <td class="mono">{{ $s->no_agenda }}</td>
              <td>{{ $s->pengirim }}</td>
              <td>{{ $s->perihal }}</td>
              <td>{{ $s->tanggal_terima->translatedFormat('d M Y') }}</td>
              <td>
                @if ($s->dispositions->isEmpty())
                  <span class="pill pill-warning"><span class="dot"></span>Menunggu disposisi</span>
                @else
                  <span class="pill pill-success"><span class="dot"></span>Ditindaklanjuti</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" style="text-align:center;color:var(--ink-faint);padding:24px;">Belum ada surat masuk.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="stack">
      <div class="card card-pad">
        <div class="card-head"><div><h2>Perlu tindak lanjut</h2><div class="sub">Surat yang belum didisposisikan</div></div></div>
        <div class="stack" style="gap:10px;">
          @forelse ($perluTindak as $s)
            <div class="info-row"><span class="k">{{ $s->perihal }} &mdash; {{ $s->pengirim }}</span></div>
          @empty
            <div class="info-row"><span class="k">Semua surat sudah didisposisikan.</span></div>
          @endforelse
        </div>
        <a class="btn btn-ghost btn-block" style="margin-top:14px;" href="{{ route('surat-masuk.index', ['status' => 'menunggu']) }}">Buka lembar disposisi</a>
      </div>
      <div class="card card-pad">
        <div class="card-head"><h2>Aksi cepat</h2></div>
        <div class="stack" style="gap:10px;">
          <a class="btn btn-primary btn-block" href="{{ route('surat-masuk.index') }}">+ Catat surat masuk</a>
          <a class="btn btn-ghost btn-block" href="{{ route('surat-keluar.index') }}">+ Buat draf surat keluar</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

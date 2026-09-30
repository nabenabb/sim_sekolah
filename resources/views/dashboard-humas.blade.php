@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumb', 'Waka Humas / Beranda')

@section('content')
<div class="stack">
  @if ($disposisiSaya > 0)
    <div style="background:var(--accent-tint);border-radius:var(--radius-control);padding:12px 16px;font-size:13.5px;display:flex;justify-content:space-between;align-items:center;">
      <span>Ada <b>{{ $disposisiSaya }}</b> disposisi yang menunggu tindak lanjut Anda.</span>
      <a class="link-more" href="{{ route('disposisi-saya.index') }}">Buka Disposisi Saya &rarr;</a>
    </div>
  @endif
  <div class="grid-4">
    <div class="stat"><div class="label">Mitra industri aktif</div><div class="value">{{ $mitraAktif }}</div><div class="delta up">+{{ $mitraBaru }} bulan ini</div></div>
    <div class="stat"><div class="label">MoU akan berakhir</div><div class="value">{{ $akanBerakhir->count() }}</div><div class="delta warn">Dalam 30 hari</div></div>
    <div class="stat"><div class="label">Pengajuan menunggu verifikasi</div><div class="value">{{ $menungguVerifikasi }}</div><div class="delta warn">Perlu ditinjau</div></div>
    <div class="stat"><div class="label">Siswa PKL aktif</div><div class="value">{{ $siswaPkl }}</div><div class="delta up">di {{ $mitraPkl }} mitra industri</div></div>
  </div>

  <div class="card card-pad">
    <div class="card-head"><div><h2>MoU mendekati kadaluarsa</h2><div class="sub">Segera hubungi mitra untuk perpanjangan</div></div></div>
    <table>
      <thead><tr><th>Mitra</th><th>Jenis Dokumen</th><th>Berakhir</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @forelse ($akanBerakhir as $doc)
          <tr>
            <td class="cell-strong">{{ $doc->partner->nama_perusahaan }}</td>
            <td>{{ $doc->jenis_dokumen }}</td>
            <td class="mono">{{ $doc->berlaku_selesai->translatedFormat('d M Y') }}</td>
            <td><span class="pill pill-danger"><span class="dot"></span>{{ (int) $today->diffInDays($doc->berlaku_selesai) }} hari lagi</span></td>
            <td><a class="link-more" href="{{ route('mitra-industri.index', ['perpanjang' => $doc->partner_id]) }}">Perpanjang &rarr;</a></td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center;color:var(--ink-faint);padding:24px;">Tidak ada MoU yang berakhir dalam 30 hari ke depan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumb', 'Kepala Sekolah / Beranda')

@section('content')
<div class="stack">
  <div class="grid-4">
    <div class="stat"><div class="label">Disposisi menunggu arahan</div><div class="value">{{ $jumlahDisposisi }}</div><div class="delta warn">Segera ditindaklanjuti</div></div>
    <div class="stat"><div class="label">Surat keluar menunggu TTD</div><div class="value">{{ $jumlahTtd }}</div><div class="delta warn">Perlu ditandatangani</div></div>
    <div class="stat"><div class="label">Kerja sama menunggu persetujuan</div><div class="value">{{ $jumlahKerjaSama }}</div><div class="delta warn">Tahap akhir</div></div>
    <div class="stat"><div class="label">MoU aktif</div><div class="value">{{ $mouAktif }}</div><div class="delta up">{{ $mouAkanBerakhir }} akan berakhir 30 hari</div></div>
  </div>

  <div class="card card-pad">
    <div class="card-head"><div><h2>Perlu tindakan Anda</h2><div class="sub">Disposisi, kerja sama, dan surat keluar yang menunggu</div></div></div>
    <table>
      <thead><tr><th>Jenis</th><th>Perihal</th><th>Dari</th><th></th></tr></thead>
      <tbody>
        @forelse ($tindakan as $t)
          <tr>
            <td><span class="pill {{ $t['pill'] }}"><span class="dot"></span>{{ $t['jenis'] }}</span></td>
            <td class="cell-strong">{{ $t['perihal'] }}</td>
            <td>{{ $t['dari'] }}</td>
            <td><a class="link-more" href="{{ $t['url'] }}">{{ $t['label'] }}</a></td>
          </tr>
        @empty
          <tr><td colspan="4" style="text-align:center;color:var(--ink-faint);padding:24px;">Tidak ada yang menunggu tindakan Anda.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

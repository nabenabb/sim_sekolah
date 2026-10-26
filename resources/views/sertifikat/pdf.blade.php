<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Sertifikat PKL {{ $nomor }}</title>
<style>
  @page { margin: 0; }
  body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #10253D; }
  .frame { margin: 20px; border: 3px solid #143B61; padding: 4px; }
  .frame-inner { min-height: 500px; border: 1px solid #D6A51D; padding: 18px 34px 14px; }
  .center { text-align: center; }
  table.head { width: 100%; border-collapse: collapse; }
  table.head td { vertical-align: middle; }
  .logo-cell { width: 88px; text-align: center; }
  .logo { width: 68px; height: 68px; object-fit: contain; }
  .school { font-size: 10px; letter-spacing: 1.2px; color: #14406B; font-weight: bold; }
  .title { font-size: 30px; font-weight: bold; color: #14406B; margin: 4px 0 0; letter-spacing: 2px; }
  .subtitle { font-size: 11px; letter-spacing: 1.5px; color: #52677C; font-weight: bold; }
  .bar { width: 92px; height: 3px; background: #D6A51D; margin: 11px auto 12px; }
  .lead { font-size: 12px; color: #5B6B7D; margin: 0; }
  .name { font-size: 25px; font-weight: bold; margin: 5px 0; color: #10253D; }
  .sub { font-size: 11px; color: #52677C; margin: 0; }
  .body-text { font-size: 12px; line-height: 1.55; margin: 10px auto 0; width: 90%; }
  table.meta { margin: 11px auto 0; font-size: 10.5px; border-collapse: collapse; }
  table.meta td { padding: 3px 10px; text-align: left; }
  table.meta td.k { color: #52677C; }
  table.foot { width: 100%; margin-top: 12px; border-collapse: collapse; }
  table.foot td { vertical-align: bottom; font-size: 10px; }
  .sign-space { height: 36px; }
  .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; color: #5B6B7D; }
  .qr { width: 66px; height: 66px; }
  .number { font-size: 9px; font-weight: bold; color: #143B61; }
</style>
</head>
<body>
@php
  $student = $placement->student;
  $tanggalMulai = $placement->tanggal_mulai->locale('id')->translatedFormat('d F Y');
  $tanggalSelesai = $placement->tanggal_selesai->locale('id')->translatedFormat('d F Y');
@endphp
<div class="frame">
  <div class="frame-inner">
    <table class="head"><tr>
      <td class="logo-cell"><img class="logo" src="{{ $logoProvinsi }}" alt="Lambang Provinsi Kepulauan Riau"></td>
      <td class="center">
        <div class="school">PEMERINTAH PROVINSI KEPULAUAN RIAU</div>
        <div class="school" style="margin-top:3px;">SMK NEGERI 4 TANJUNGPINANG</div>
        <div class="title">SERTIFIKAT</div>
        <div class="subtitle">PRAKTIK KERJA LAPANGAN</div>
      </td>
      <td class="logo-cell"><img class="logo" src="{{ $logoSekolah }}" alt="Lambang SMK Negeri 4 Tanjungpinang"></td>
    </tr></table>
    <div class="bar"></div>
    <div class="center">
      <p class="lead">Diberikan kepada</p>
      <p class="name">{{ $student->nama }}</p>
      <p class="sub">NIS {{ $student->nis }} | {{ $student->programKeahlian->nama_jurusan ?? '-' }}</p>
      <p class="body-text">
        Telah menyelesaikan Praktik Kerja Lapangan di <b>{{ $placement->partner->nama_perusahaan }}</b>
        dengan baik pada periode <b>{{ $tanggalMulai }} sampai {{ $tanggalSelesai }}</b>.
      </p>
      <table class="meta">
        <tr><td class="k">Nilai akhir</td><td>:</td><td><b>{{ rtrim(rtrim(number_format($placement->nilai, 2, ',', '.'), '0'), ',') }}</b></td></tr>
        <tr><td class="k">Predikat</td><td>:</td><td><b>{{ $placement->nilai >= 85 ? 'Sangat Baik' : ($placement->nilai >= 75 ? 'Baik' : ($placement->nilai >= 60 ? 'Cukup' : 'Kurang')) }}</b></td></tr>
      </table>
    </div>

    <table class="foot">
      <tr>
        <td style="width:22%;">
          <img class="qr" src="{{ $qrDataUri }}" alt="QR verifikasi"><br>
          <span class="number">{{ $nomor }}</span>
        </td>
        <td style="width:26%;" class="center">
          Pembimbing Industri
          <div class="sign-space"></div>
          <b><u>{{ $placement->pembimbing_industri ?: '........................................' }}</u></b>
        </td>
        <td style="width:26%;" class="center">
          Mengetahui,<br>Kepala SMK Negeri 4 Tanjungpinang<br>
          <div class="sign-space"></div>
          <b><u>{{ $namaKepsek }}</u></b>
        </td>
        <td style="width:26%;" class="center">
          Tanjungpinang, {{ $tanggalTerbit->locale('id')->translatedFormat('d F Y') }}<br>
          Pindai QR untuk verifikasi<br>
          <span class="mono">Dokumen elektronik resmi</span>
        </td>
      </tr>
    </table>
  </div>
</div>
</body>
</html>

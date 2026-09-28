<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Sertifikat PKL {{ $nomor }}</title>
<style>
  @page { margin: 0; }
  body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #10253D; }
  .frame { margin: 22px; border: 3px solid #14406B; padding: 4px; }
  .frame-inner { border: 1px solid #F5B400; padding: 22px 40px 18px 40px; }
  .center { text-align: center; }
  .school { font-size: 13px; letter-spacing: 2px; color: #14406B; font-weight: bold; }
  .title { font-size: 28px; font-weight: bold; color: #14406B; margin: 6px 0 2px 0; letter-spacing: 1px; }
  .bar { width: 90px; height: 3px; background: #F5B400; margin: 8px auto 14px auto; }
  .lead { font-size: 12px; color: #5B6B7D; margin: 0; }
  .name { font-size: 26px; font-weight: bold; margin: 8px 0 2px 0; color: #10253D; }
  .sub { font-size: 12px; color: #5B6B7D; margin: 0 0 10px 0; }
  .body-text { font-size: 12.5px; line-height: 1.6; margin: 0 auto; width: 88%; }
  table.meta { margin: 12px auto 0 auto; font-size: 12px; border-collapse: collapse; }
  table.meta td { padding: 3px 12px; text-align: left; }
  table.meta td.k { color: #5B6B7D; }
  table.foot { width: 100%; margin-top: 18px; border-collapse: collapse; }
  table.foot td { vertical-align: bottom; font-size: 11px; }
  .sign-space { height: 46px; }
  .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 10.5px; color: #5B6B7D; }
</style>
</head>
<body>
@php $student = $placement->student; @endphp
<div class="frame">
  <div class="frame-inner">
    <div class="center">
      <div class="school">SMK NEGERI 4 TANJUNGPINANG</div>
      <div class="title">SERTIFIKAT</div>
      <div class="school" style="letter-spacing:1px;">PRAKTIK KERJA LAPANGAN</div>
      <div class="bar"></div>
      <p class="lead">Diberikan kepada</p>
      <p class="name">{{ $student->nama }}</p>
      <p class="sub">NIS {{ $student->nis }} &middot; {{ $student->programKeahlian->nama_jurusan ?? '' }}</p>
    </div>

    <p class="body-text center">
      telah menyelesaikan Praktik Kerja Lapangan di <b>{{ $placement->partner->nama_perusahaan }}</b>
      pada periode <b>{{ $placement->periodeText() }}</b>
      dengan nilai akhir <b>{{ $placement->nilai + 0 }} ({{ $placement->predikat() }})</b>.
    </p>

    <table class="foot">
      <tr>
        <td style="width:30%;">
          <img src="{{ $qrDataUri }}" width="86" height="86" alt="QR verifikasi"><br>
          <span class="mono">{{ $nomor }}</span>
        </td>
        <td style="width:30%;" class="center">
          <span class="mono">Pindai QR untuk<br>memverifikasi keaslian</span>
        </td>
        <td style="width:40%;" class="center">
          Tanjungpinang, {{ $tanggalTerbit->translatedFormat('d F Y') }}<br>
          Kepala SMK Negeri 4 Tanjungpinang
          <div class="sign-space"></div>
          <b><u>{{ $namaKepsek }}</u></b>
        </td>
      </tr>
    </table>
  </div>
</div>
</body>
</html>

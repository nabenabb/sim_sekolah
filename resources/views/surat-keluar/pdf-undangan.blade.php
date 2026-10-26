<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $surat->no_surat }}</title>
<style>
  @page { margin: 20mm 23mm 18mm; }
  body { margin: 0; color: #111; font-family: 'DejaVu Serif', serif; font-size: 12pt; line-height: 1.45; }
  table.kop { width: 100%; border-collapse: collapse; }
  table.kop td { vertical-align: middle; }
  .logo-cell { width: 70px; text-align: center; }
  .logo { width: 58px; height: 58px; object-fit: contain; }
  .kop-text { text-align: center; font-family: 'DejaVu Serif', serif; color: #111; }
  .kop-text .t1 { font-size: 12px; font-weight: bold; }
  .kop-text .t2 { margin-top: 2px; font-size: 13px; font-weight: bold; }
  .kop-text .t3 { margin-top: 2px; font-size: 17px; font-weight: bold; }
  .kop-text .t4 { margin-top: 3px; font-size: 9.5px; line-height: 1.4; }
  .kop-line { border-bottom: 2px solid #173F66; border-top: 1px solid #173F66; margin-top: 8px; height: 2px; }
  .kop-line-thin { display: none; }
  .tanggal { text-align: right; margin: 17px 0 12px; }
  table.meta { border-collapse: collapse; margin-bottom: 13px; }
  table.meta td { padding: 1px 4px 1px 0; vertical-align: top; }
  table.meta td.label { width: 65px; }
  table.meta td.titik { width: 12px; }
  .isi p { margin: 0 0 9px; text-align: justify; }
  table.jadwal { width: 88%; margin: 10px auto 14px; border-collapse: collapse; }
  table.jadwal td { padding: 2px 8px; vertical-align: top; }
  table.jadwal td.label { width: 120px; }
  table.jadwal td.titik { width: 10px; }
  table.ttd { width: 100%; margin-top: 20px; border-collapse: collapse; }
  table.ttd td { vertical-align: top; }
  .ttd-space { height: 52px; }
  .status { position: fixed; top: 0; right: 0; border: 1px solid #B77A00; padding: 4px 9px; color: #9A6800; font-size: 8px; font-weight: bold; letter-spacing: 1px; }
</style>
</head>
<body>
@if ($surat->status !== 'terkirim')
  <div class="status">{{ $surat->status === 'menunggu_ttd' ? 'MENUNGGU TANDA TANGAN' : 'DRAF' }}</div>
@endif

<table class="kop">
  <tr>
    <td class="logo-cell"><img class="logo" src="{{ $logoProvinsi }}" alt="Lambang Provinsi Kepulauan Riau"></td>
    <td class="kop-text">
      <div class="t1">PEMERINTAH PROVINSI KEPULAUAN RIAU</div>
      <div class="t2">DINAS PENDIDIKAN</div>
      <div class="t3">SMK NEGERI 4 TANJUNGPINANG</div>
      <div class="t4">Jalan Nusantara Km. 14, Batu IX, Tanjungpinang, Kepulauan Riau 29125</div>
      <div class="t4">Telepon (0771) 4440844</div>
      <div class="t4">Laman smkn4-tanjungpinang.sch.id · Pos-el smkn4tpi4@gmail.com</div>
    </td>
    <td class="logo-cell"><img class="logo" src="{{ $logoSekolah }}" alt="Lambang SMK Negeri 4 Tanjungpinang"></td>
  </tr>
</table>
<div class="kop-line"></div>
<div class="kop-line-thin"></div>

<div class="tanggal">Tanjungpinang, {{ $surat->tanggal->locale('id')->translatedFormat('d F Y') }}</div>

<table class="meta">
  <tr><td class="label">Nomor</td><td class="titik">:</td><td>{{ $surat->no_surat }}</td></tr>
  <tr><td class="label">Sifat</td><td class="titik">:</td><td>{{ $data['sifat'] ?? 'Biasa' }}</td></tr>
  <tr><td class="label">Lampiran</td><td class="titik">:</td><td>{{ $data['lampiran'] ?? '-' }}</td></tr>
  <tr><td class="label">Hal</td><td class="titik">:</td><td>{{ $surat->perihal }}</td></tr>
</table>

<div class="isi">
  <p>
    Yth. {{ $surat->tujuan }}<br>
    di<br>
    {{ $data['lokasi_tujuan'] ?? 'Tanjungpinang' }}
  </p>

  <p>Dengan hormat,</p>
  <p>{!! nl2br(e($data['pembuka'] ?? $surat->isi_ringkas ?? '')) !!}</p>

  <table class="jadwal">
    <tr><td class="label">Hari, tanggal</td><td class="titik">:</td><td>{{ $data['hari_tanggal'] ?? '-' }}</td></tr>
    <tr><td class="label">Pukul</td><td class="titik">:</td><td>{{ $data['pukul'] ?? '-' }}</td></tr>
    <tr><td class="label">Tempat</td><td class="titik">:</td><td>{{ $data['tempat'] ?? '-' }}</td></tr>
  </table>

  <p>{!! nl2br(e($data['penutup'] ?? 'Atas perhatian dan kehadiran Bapak/Ibu diucapkan terima kasih.')) !!}</p>
</div>

<table class="ttd">
  <tr>
    <td style="width:60%;"></td>
    <td style="width:40%;text-align:center;">
      {{ $data['jabatan_kepsek'] ?? 'Kepala Sekolah,' }}
      <div class="ttd-space"></div>
      <b><u>{{ $namaKepsek }}</u></b><br>
      NIP {{ $data['nip_kepsek'] ?? '................................' }}
    </td>
  </tr>
</table>

</body>
</html>

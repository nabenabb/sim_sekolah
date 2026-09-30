<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $surat->no_surat }}</title>
<style>
  @page { margin: 25px 45px; }
  body { margin: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #111; line-height: 1.5; }
  table.kop { width: 100%; border-collapse: collapse; }
  table.kop td { vertical-align: middle; }
  .logo-box { width: 62px; height: 62px; border: 1px solid #333; border-radius: 50%; text-align: center; font-size: 8px; color: #666; }
  .logo-box .inner { padding-top: 22px; }
  .kop-text { text-align: center; }
  .kop-text .t1 { font-size: 12px; }
  .kop-text .t2 { font-size: 17px; font-weight: bold; letter-spacing: 0.5px; }
  .kop-text .t3 { font-size: 15px; font-weight: bold; }
  .kop-text .t4 { font-size: 10.5px; }
  .kop-line { border-bottom: 3px solid #111; border-top: 1px solid #111; margin-top: 4px; height: 3px; }
  .kop-line-thin { border-bottom: 1px solid #111; margin-bottom: 14px; }

  .tanggal { text-align: right; margin: 18px 0 14px 0; }
  table.meta { border-collapse: collapse; margin-bottom: 14px; }
  table.meta td { padding: 1px 0; vertical-align: top; }
  table.meta td.label { width: 70px; }
  table.meta td.titik { width: 12px; }

  .isi p { margin: 0 0 10px 0; text-align: justify; }
  table.jadwal { margin: 4px 0 14px 30px; border-collapse: collapse; }
  table.jadwal td { padding: 2px 0; vertical-align: top; }
  table.jadwal td.label { width: 90px; }
  table.jadwal td.titik { width: 12px; }

  table.ttd { width: 100%; margin-top: 26px; border-collapse: collapse; }
  table.ttd td { vertical-align: top; }
  .ttd-space { height: 55px; }
</style>
</head>
<body>

<table class="kop">
  <tr>
    <td style="width:70px;"><div class="logo-box"><div class="inner">LOGO<br>DISDIK</div></div></td>
    <td class="kop-text">
      <div class="t1">PEMERINTAH PROVINSI KEPULAUAN RIAU</div>
      <div class="t2">DINAS PENDIDIKAN</div>
      <div class="t3">SMK NEGERI 4 TANJUNGPINANG</div>
      <div class="t4">Jalan Nusantara Km. 14, Batu IX, Tanjungpinang, Kepulauan Riau 29125</div>
      <div class="t4">Telepon (0771) 4440844</div>
      <div class="t4">Laman smkn4-tanjungpinang.sch.id, Pos-el smkntpi4@gmail.com</div>
    </td>
    <td style="width:70px;"><div class="logo-box"><div class="inner">LOGO<br>SMKN4</div></div></td>
  </tr>
</table>
<div class="kop-line"></div>
<div class="kop-line-thin"></div>

<div class="tanggal">Tanjungpinang, {{ $surat->tanggal->translatedFormat('d F Y') }}</div>

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

  <p>{{ $data['pembuka'] ?? $surat->isi_ringkas }}</p>

  <table class="jadwal">
    <tr><td class="label">Hari, tanggal</td><td class="titik">:</td><td>{{ $data['hari_tanggal'] ?? '-' }}</td></tr>
    <tr><td class="label">Pukul</td><td class="titik">:</td><td>{{ $data['pukul'] ?? '-' }}</td></tr>
    <tr><td class="label">Tempat</td><td class="titik">:</td><td>{{ $data['tempat'] ?? '-' }}</td></tr>
  </table>

  <p>{{ $data['penutup'] ?? 'Atas perhatian dan kehadiran Bapak/Ibu diucapkan terima kasih.' }}</p>
</div>

<table class="ttd">
  <tr>
    <td style="width:60%;"></td>
    <td style="width:40%;text-align:center;">
      Kepala Sekolah,
      <div class="ttd-space"></div>
      <b><u>{{ $namaKepsek }}</u></b><br>
      {{ $data['jabatan_kepsek'] ?? 'Pembina Tk.I' }}<br>
      NIP {{ $data['nip_kepsek'] ?? '_________________' }}
    </td>
  </tr>
</table>

</body>
</html>

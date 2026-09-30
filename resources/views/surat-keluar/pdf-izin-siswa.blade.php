<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $surat->no_surat }}</title>
<style>
  @page { margin: 25px 45px; }
  body { margin: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #111; line-height: 1.5; }
  table.kop { width: 100%; border-collapse: collapse; }
  .logo-box { width: 52px; height: 52px; border: 1px solid #333; border-radius: 50%; text-align: center; font-size: 7px; color: #666; }
  .logo-box .inner { padding-top: 18px; }
  .kop-text { text-align: center; }
  .kop-text .t1 { font-size: 11px; }
  .kop-text .t2 { font-size: 14px; font-weight: bold; }
  .kop-text .t3 { font-size: 13px; font-weight: bold; }
  .kop-text .t4 { font-size: 9.5px; }
  .kop-line { border-bottom: 3px solid #111; border-top: 1px solid #111; margin-top: 4px; height: 3px; }
  .kop-line-thin { border-bottom: 1px solid #111; margin-bottom: 16px; }

  .judul { text-align: center; font-weight: bold; text-decoration: underline; font-size: 14px; margin: 8px 0 18px 0; }

  table.meta { border-collapse: collapse; margin: 0 auto 22px auto; }
  table.meta td { padding: 3px 0; vertical-align: top; }
  table.meta td.label { width: 140px; }
  table.meta td.titik { width: 14px; }
  table.meta td.isian { min-width: 260px; border-bottom: 1px dotted #999; }

  table.ttd { width: 100%; margin-top: 30px; border-collapse: collapse; }
  table.ttd td { vertical-align: top; text-align: center; width: 50%; }
  .ttd-space { height: 55px; }
  .kanan { text-align: right; margin-bottom: 8px; }
</style>
</head>
<body>

<table class="kop">
  <tr>
    <td style="width:60px;"><div class="logo-box"><div class="inner">LOGO<br>DISDIK</div></div></td>
    <td class="kop-text">
      <div class="t1">PEMERINTAH PROVINSI KEPULAUAN RIAU</div>
      <div class="t1">DINAS PENDIDIKAN</div>
      <div class="t3">SMK NEGERI 4 TANJUNGPINANG</div>
      <div class="t4">Jalan Nusantara Km. 14, Batu IX, Tanjungpinang, Kepulauan Riau 29125</div>
      <div class="t4">Telepon (0771) 4440844</div>
      <div class="t4">Laman smkn4-tanjungpinang.sch.id, Pos-el smkntpi4@gmail.com</div>
    </td>
    <td style="width:60px;"></td>
  </tr>
</table>
<div class="kop-line"></div>
<div class="kop-line-thin"></div>

<div class="judul">SURAT IZIN SISWA</div>

<div class="kanan">Tanjungpinang, {{ $surat->tanggal->translatedFormat('d F Y') }}</div>

<table class="meta">
  <tr><td class="label">Nama Siswa</td><td class="titik">:</td><td class="isian">{{ $data['nama_siswa'] }}</td></tr>
  <tr><td class="label">Nomor Induk Siswa</td><td class="titik">:</td><td class="isian">{{ $data['nis'] }}</td></tr>
  <tr><td class="label">Kelas</td><td class="titik">:</td><td class="isian">{{ $data['kelas'] }}</td></tr>
  <tr><td class="label">Tujuan Izin</td><td class="titik">:</td><td class="isian">{{ $data['tujuan_izin'] }}</td></tr>
  <tr><td class="label">Jam</td><td class="titik">:</td><td class="isian">{{ $data['jam_keluar'] }} s.d {{ $data['jam_kembali'] }}</td></tr>
</table>

<table class="ttd">
  <tr>
    <td>
      Guru Piket
      <div class="ttd-space"></div>
      <div style="border-top:1px solid #111;width:70%;margin:0 auto;padding-top:2px;">NIP.</div>
    </td>
    <td>
      Wakil Kepala Sekolah
      <div class="ttd-space"></div>
      <div style="border-top:1px solid #111;width:70%;margin:0 auto;padding-top:2px;">NIP.</div>
    </td>
  </tr>
</table>

</body>
</html>

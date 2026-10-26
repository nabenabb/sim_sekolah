<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $surat->no_surat }}</title>
<style>
  @page { margin: 20mm 24mm 18mm; }
  body { margin: 0; color: #111; font-family: 'DejaVu Serif', serif; font-size: 12pt; line-height: 1.5; }
  table.kop { width: 100%; border-collapse: collapse; }
  table.kop td { vertical-align: middle; }
  .logo-cell { width: 66px; text-align: center; }
  .logo { width: 54px; height: 54px; object-fit: contain; }
  .kop-text { text-align: center; font-family: 'DejaVu Serif', serif; color: #111; }
  .kop-text .t1 { font-size: 12px; font-weight: bold; }
  .kop-text .t2 { margin-top: 2px; font-size: 13px; font-weight: bold; }
  .kop-text .t3 { margin-top: 2px; font-size: 17px; font-weight: bold; }
  .kop-text .t4 { margin-top: 3px; font-size: 9.5px; line-height: 1.4; }
  .kop-line { border-bottom: 2px solid #173F66; border-top: 1px solid #173F66; margin-top: 8px; height: 2px; }
  .kop-line-thin { display: none; }
  .judul { margin: 25px 0 4px; color: #123D63; font-family: 'DejaVu Serif', serif; font-size: 16px; font-weight: bold; letter-spacing: .8px; text-align: center; }
  .nomor { margin-bottom: 21px; color: #52677C; font-size: 10px; text-align: center; }
  .kanan { margin: 0 0 17px; text-align: right; }
  .intro { margin-bottom: 16px; text-align: justify; }
  table.meta { width: 100%; margin: 0 0 23px; border: 1px solid #C7D5E2; }
  table.meta td { padding: 8px 10px; border-bottom: 1px solid #DCE5EE; vertical-align: top; }
  table.meta tr:last-child td { border-bottom: 0; }
  table.meta td.label { width: 145px; background: #F2F6FA; color: #52677C; }
  table.meta td.titik { width: 15px; background: #F2F6FA; }
  table.meta td.isian { color: #172B40; }
  table.ttd { width: 100%; margin-top: 28px; border-collapse: collapse; }
  table.ttd td { width: 50%; vertical-align: top; text-align: center; }
  .ttd-space { height: 60px; }
  .signature-line { width: 74%; margin: 0 auto; padding-top: 4px; border-top: 1px solid #52677C; }
  .notice { margin-top: 22px; padding: 8px 10px; border: 1px solid #DCE5EE; background: #F5F8FB; color: #52677C; font-size: 9px; text-align: center; }
</style>
</head>
<body>

<table class="kop">
  <tr>
    <td class="logo-cell"><img class="logo" src="{{ $logoProvinsi }}" alt="Lambang Provinsi Kepulauan Riau"></td>
    <td class="kop-text">
      <div class="t1">PEMERINTAH PROVINSI KEPULAUAN RIAU</div>
      <div class="t1">DINAS PENDIDIKAN</div>
      <div class="t3">SMK NEGERI 4 TANJUNGPINANG</div>
      <div class="t4">Jalan Nusantara Km. 14, Batu IX, Tanjungpinang, Kepulauan Riau 29125</div>
      <div class="t4">Telepon (0771) 4440844</div>
      <div class="t4">Laman smkn4-tanjungpinang.sch.id, Pos-el smkn4tpi4@gmail.com</div>
    </td>
    <td class="logo-cell"><img class="logo" src="{{ $logoSekolah }}" alt="Lambang SMK Negeri 4 Tanjungpinang"></td>
  </tr>
</table>
<div class="kop-line"></div>
<div class="kop-line-thin"></div>

<div class="judul">SURAT IZIN KELUAR SISWA</div>
<div class="nomor">Nomor: {{ $surat->no_surat }}</div>

<div class="kanan">Tanjungpinang, {{ $surat->tanggal->locale('id')->translatedFormat('d F Y') }}</div>

<p class="intro">Yang bertanda tangan di bawah ini memberikan izin kepada siswa berikut untuk meninggalkan kegiatan belajar di sekolah:</p>

<table class="meta">
  <tr><td class="label">Nama Siswa</td><td class="titik">:</td><td class="isian">{{ $data['nama_siswa'] }}</td></tr>
  <tr><td class="label">Nomor Induk Siswa</td><td class="titik">:</td><td class="isian">{{ $data['nis'] }}</td></tr>
  <tr><td class="label">Kelas</td><td class="titik">:</td><td class="isian">{{ $data['kelas'] }}</td></tr>
  <tr><td class="label">Tujuan Izin</td><td class="titik">:</td><td class="isian">{{ $data['tujuan_izin'] }}</td></tr>
  <tr><td class="label">Jam</td><td class="titik">:</td><td class="isian">{{ $data['jam_keluar'] }} s.d {{ $data['jam_kembali'] }}</td></tr>
</table>

<table class="ttd">
  <tr>
    <td>Guru Piket<div class="ttd-space"></div><div class="signature-line"><b>(........................................)</b><br>NIP. ................................</div></td>
    <td>Wakil Kepala Sekolah<div class="ttd-space"></div><div class="signature-line"><b>(........................................)</b><br>NIP. ................................</div></td>
  </tr>
</table>

<div class="notice">Surat ini berlaku pada tanggal dan jam yang tercantum di atas. Siswa wajib kembali ke sekolah setelah urusan selesai.</div>

</body>
</html>

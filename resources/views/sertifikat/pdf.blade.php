<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Sertifikat PKL {{ $nomor }}</title>
<style>
  @page { margin: 0; }
  body { margin: 0; color: #111; font-family: 'DejaVu Sans', sans-serif; }
  .certificate { position: fixed; top: 9px; right: 9px; bottom: 9px; left: 9px; overflow: hidden; border: 2px solid #202b34; background: #fff; }
  .corner-top { position: absolute; top: 0; right: 0; width: 0; height: 0; border-top: 300px solid #12b6da; border-left: 300px solid transparent; }
  .corner-top-light { position: absolute; top: 0; right: 0; width: 0; height: 0; border-top: 205px solid #a8e9f3; border-left: 205px solid transparent; }
  .corner-bottom { position: absolute; bottom: 0; left: 0; width: 0; height: 0; border-bottom: 220px solid #12b6da; border-right: 220px solid transparent; }
  .corner-bottom-light { position: absolute; bottom: 0; left: 0; width: 0; height: 0; border-bottom: 145px solid #a8e9f3; border-right: 145px solid transparent; }
  .watermark { position: absolute; top: 260px; left: 50%; width: 245px; height: 245px; margin-left: -122px; opacity: .10; }
  .logo { position: absolute; top: 44px; width: 112px; height: 112px; object-fit: contain; }
  .logo-left { left: 74px; }
  .logo-right { right: 74px; }
  .title { position: absolute; top: 28px; left: 0; width: 100%; text-align: center; font-family: 'DejaVu Serif', serif; font-size: 76px; line-height: 1.2; }
  .number { position: absolute; top: 126px; left: 0; width: 100%; text-align: center; font-family: 'DejaVu Serif', serif; font-size: 19px; }
  .recipient { position: absolute; top: 180px; left: 90px; right: 90px; text-align: center; }
  .lead { margin: 0 0 18px; font-size: 20px; }
  .student-name { margin: 0 0 28px; font-family: 'DejaVu Serif', serif; font-size: 38px; font-style: italic; }
  table.details { margin: 0 auto 20px; font-size: 18px; border-collapse: collapse; }
  table.details td { padding: 3px 8px; text-align: left; }
  .award { max-width: 790px; margin: 22px auto 0; font-size: 17px; line-height: 1.45; }
  .signature { position: absolute; top: 545px; left: 4%; right: 4%; width: 92%; border-collapse: collapse; }
  .signature td { width: 40%; vertical-align: top; text-align: center; font-size: 15px; line-height: 1.45; }
  .signature td.qr-cell { width: 20%; vertical-align: middle; }
  .sign-space { height: 72px; }
  .sign-name { font-weight: bold; text-decoration: underline; }
  .nip { margin-top: 14px; }
  .qr { width: 62px; height: 62px; }
  .qr-note { font-size: 9px; }
</style>
</head>
<body>
@php
  $student = $placement->student;
  $tanggalMulai = $placement->tanggal_mulai->locale('id')->translatedFormat('d F Y');
  $tanggalSelesai = $placement->tanggal_selesai->locale('id')->translatedFormat('d F Y');
  $tanggalLahir = $student->tanggal_lahir?->locale('id')->translatedFormat('d F Y') ?? '-';
@endphp
<div class="certificate">
  <div class="corner-top"></div><div class="corner-top-light"></div>
  <div class="corner-bottom"></div><div class="corner-bottom-light"></div>
  <img class="watermark" src="{{ $logoProvinsi }}" alt="">
  <img class="logo logo-left" src="{{ $logoProvinsi }}" alt="Lambang Provinsi Kepulauan Riau">
  <img class="logo logo-right" src="{{ $logoSekolah }}" alt="Lambang SMK Negeri 4 Tanjungpinang">

  <div class="title">Sertifikat</div>
  <div class="number">Nomor: {{ $nomor }}</div>

  <div class="recipient">
    <p class="lead">Diberikan Kepada</p>
    <p class="student-name">{{ $student->nama }}</p>
    <table class="details">
      <tr><td>Nomor Induk Siswa</td><td>:</td><td>{{ $student->nis }}</td></tr>
      <tr><td>Tempat / Tgl. Lahir</td><td>:</td><td>{{ $student->tempat_lahir ? $student->tempat_lahir . ' / ' . $tanggalLahir : $tanggalLahir }}</td></tr>
      <tr><td>Konsentrasi Keahlian</td><td>:</td><td>{{ $student->programKeahlian->nama_jurusan ?? '-' }}</td></tr>
    </table>
    <p class="award">
      Telah selesai mengikuti Praktik Kerja Lapangan (PKL) pada<br>
      <b>{{ $placement->partner->nama_perusahaan }}</b> dengan predikat <b>{{ $placement->predikat() }}</b>,
      pada {{ $tanggalMulai }} sampai {{ $tanggalSelesai }}.
    </p>
  </div>

  <table class="signature">
    <tr>
      <td>
        KEPALA<br>SMK NEGERI 4 TANJUNGPINANG
        <div class="sign-space"></div>
        <div class="sign-name">{{ $namaKepsek }}</div>
        <div class="nip">NIP ................................</div>
      </td>
      <td class="qr-cell">
        <img class="qr" src="{{ $qrDataUri }}" alt="QR verifikasi"><br>
        <span class="qr-note">Pindai untuk verifikasi</span>
      </td>
      <td>
        PEMBIMBING INDUSTRI<br>{{ $placement->partner->nama_perusahaan }}
        <div class="sign-space"></div>
        <div class="sign-name">{{ $placement->pembimbing_industri ?: '........................................' }}</div>
      </td>
    </tr>
  </table>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $surat->no_surat }}</title>
<style>
  @page { margin: 22mm 24mm 20mm; }
  body { margin: 0; color: #111; font-family: 'DejaVu Serif', serif; font-size: 12pt; line-height: 1.5; }
  table { border-collapse: collapse; }
  .letterhead { width: 100%; }
  .letterhead td { vertical-align: middle; }
  .logo-cell { width: 86px; text-align: center; }
  .logo { display: block; width: auto; height: 72px; margin: 0 auto; }
  .school { text-align: center; font-family: 'DejaVu Serif', serif; color: #111; }
  .school .province { font-size: 12px; font-weight: bold; }
  .school .office { margin-top: 2px; font-size: 13px; font-weight: bold; }
  .school .name { margin-top: 2px; font-size: 17px; font-weight: bold; }
  .school .address { margin-top: 3px; font-size: 9.5px; line-height: 1.4; }
  .rule { margin-top: 9px; border-top: 2px solid #173F66; border-bottom: 1px solid #173F66; height: 2px; }
  .place-date { margin: 22px 0 15px; text-align: right; }
  .meta { width: auto; margin-bottom: 22px; }
  .meta td { padding: 1px 4px 1px 0; vertical-align: top; }
  .meta .label { width: 72px; }
  .meta .colon { width: 12px; }
  .recipient { margin: 0 0 20px; }
  .subject { margin: 12px 0 18px; padding: 8px 10px; border-left: 3px solid #D8A51B; background: #F5F8FB; }
  .body-copy { text-align: justify; white-space: normal; }
  .body-copy p { margin: 0 0 12px; }
  .closing { margin-top: 18px; text-align: justify; }
  .signature { width: 100%; margin-top: 28px; }
  .signature td { vertical-align: top; }
  .signature .signer { width: 42%; text-align: left; }
  .signature-space { height: 48px; }
  .status { position: fixed; top: 0; right: 0; border: 1px solid #B77A00; padding: 4px 9px; color: #9A6800; font-size: 8px; font-weight: bold; letter-spacing: 1px; }
  .reference { margin-top: 18px; color: #71859A; font-size: 8px; }
</style>
</head>
<body>
@if ($surat->status !== 'terkirim')
  <div class="status">{{ $surat->status === 'menunggu_ttd' ? 'MENUNGGU TANDA TANGAN' : 'DRAF' }}</div>
@endif
<table class="letterhead">
  <tr>
    <td class="logo-cell"><img class="logo" src="{{ $logoProvinsi }}" alt="Lambang Provinsi Kepulauan Riau"></td>
    <td class="school">
      <div class="province">PEMERINTAH PROVINSI KEPULAUAN RIAU</div>
      <div class="office">DINAS PENDIDIKAN</div>
      <div class="name">SMK NEGERI 4 TANJUNGPINANG</div>
      <div class="address">Jalan Nusantara Km. 14, Batu IX, Tanjungpinang, Kepulauan Riau 29125<br>Telepon (0771) 4440844 · Laman smkn4-tanjungpinang.sch.id · Pos-el smkn4tpi4@gmail.com</div>
    </td>
    <td class="logo-cell"><img class="logo" src="{{ $logoSekolah }}" alt="Lambang SMK Negeri 4 Tanjungpinang"></td>
  </tr>
</table>
<div class="rule"></div>

<div class="place-date">Tanjungpinang, {{ $surat->tanggal->locale('id')->translatedFormat('d F Y') }}</div>
<table class="meta">
  <tr><td class="label">Nomor</td><td class="colon">:</td><td>{{ $surat->no_surat }}</td></tr>
  @if ($surat->refLetter)<tr><td class="label">Referensi</td><td class="colon">:</td><td>{{ $surat->refLetter->no_agenda }}</td></tr>@endif
  <tr><td class="label">Lampiran</td><td class="colon">:</td><td>{{ $surat->file_path ? '1 berkas' : '-' }}</td></tr>
</table>

<div class="recipient">Yth. {{ $surat->tujuan }}<br>di tempat</div>
<div class="subject"><strong>Perihal:</strong> {{ $surat->perihal }}</div>
<p>Dengan hormat,</p>
<div class="body-copy">{!! nl2br(e($surat->isi_ringkas ?: '')) !!}</div>
<p class="closing">Demikian surat ini kami sampaikan. Atas perhatian dan kerja sama yang baik, kami ucapkan terima kasih.</p>

<table class="signature"><tr><td></td><td class="signer">
  Tanjungpinang,<br>{{ $data['jabatan_kepsek'] ?? 'Kepala Sekolah' }}
  <div class="signature-space"></div>
  <strong><u>{{ $namaKepsek }}</u></strong><br>
  NIP {{ $data['nip_kepsek'] ?? '................................' }}
</td></tr></table>
<div class="reference">Dokumen ini dibuat melalui SIM Sekolah SMKN 4 Tanjungpinang.</div>
</body>
</html>

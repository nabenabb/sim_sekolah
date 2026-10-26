<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verifikasi Sertifikat — SMKN 4 Tanjungpinang</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="background:var(--surface-sunken);min-height:100vh;">
<div style="max-width:520px;margin:0 auto;padding:32px 18px;">

  <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;">
    <div class="brand-mark">S4</div>
    <div>
      <div style="font-weight:800;font-size:15px;">SIM Sekolah</div>
      <div style="font-size:12px;color:var(--ink-faint);">SMKN 4 Tanjungpinang &middot; Verifikasi Sertifikat</div>
    </div>
  </div>

  <div class="card card-pad">
    @if ($certificate)
      @php
        $p = $certificate->placement;
      @endphp
      <div class="verify-badge"><span>&#10003;</span> Sertifikat ini asli dan terdaftar di sistem SMKN 4 Tanjungpinang</div>
      <div class="info-list">
        <div class="info-row"><span class="k">Nomor Sertifikat</span><span class="v mono">{{ $certificate->nomor_sertifikat }}</span></div>
        <div class="info-row"><span class="k">Nama Siswa</span><span class="v">{{ $p->student->nama }}</span></div>
        <div class="info-row"><span class="k">NIS</span><span class="v">{{ $p->student->nis }}</span></div>
        <div class="info-row"><span class="k">Program Keahlian</span><span class="v">{{ $p->student->programKeahlian->nama_jurusan ?? '—' }}</span></div>
        <div class="info-row"><span class="k">Industri Tujuan</span><span class="v">{{ $p->partner->nama_perusahaan }}</span></div>
        <div class="info-row"><span class="k">Periode PKL</span><span class="v">{{ $p->periodeText() }}</span></div>
        <div class="info-row"><span class="k">Tanggal Terbit</span><span class="v">{{ $certificate->tanggal_terbit->translatedFormat('d M Y') }}</span></div>
      </div>
    @else
      <div style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:14px 16px;font-size:14px;font-weight:600;">
        &#10007; Sertifikat tidak ditemukan. Kode verifikasi tidak terdaftar di sistem.
      </div>
      <p style="font-size:13px;color:var(--ink-faint);margin-top:12px;">Pastikan Anda memindai QR yang tercetak di sertifikat asli. Jika ragu, hubungi pihak sekolah.</p>
    @endif
  </div>
</div>
</body>
</html>

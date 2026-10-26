<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — SIM Sekolah SMKN 4</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth-shell">
  <div class="auth-side">
    <div class="brand" style="border:none;padding:0;">
      <img class="login-brand-logo" src="{{ asset('images/logo-smkn4-login.jpg') }}" alt="Logo SMKN 4 Tanjungpinang">
      <div class="brand-text"><div class="t1">SIM Sekolah</div><div class="t2" style="color:#8FA6BC;">SMKN 4 Tanjungpinang</div></div>
    </div>
    <div>
      <div class="quote">&ldquo;Satu sistem untuk persuratan, kerja sama industri, dan sertifikat PKL &mdash; dari TU sampai Kepala Sekolah.&rdquo;</div>
      <div class="quote-by">Sistem Informasi Manajemen Sekolah</div>
    </div>
    <div style="font-size:12px;color:#6C87A0;">&copy; {{ date('Y') }} SMKN 4 Tanjungpinang</div>
  </div>
  <div class="auth-form-wrap">
    <div class="auth-card">
      <h1>Masuk ke akun Anda</h1>
      <p class="sub">Gunakan akun yang terdaftar di sekolah untuk mengakses sistem.</p>

      @if (session('status'))
        <div style="background:var(--success-tint);color:var(--success);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:18px;">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:18px;">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('login.submit') }}">
        @csrf

        <div class="field">
          <label>Email</label>
          <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@smkn4tanjungpinang.sch.id" required autofocus autocomplete="username">
        </div>
        <div class="field">
          <label>Kata Sandi</label>
          <input type="password" name="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
          <div class="hint"><a href="{{ route('password.request') }}" style="color:var(--primary);font-weight:600;">Lupa kata sandi?</a></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Masuk</button>
      </form>

      <p style="font-size:12px;color:var(--ink-faint);text-align:center;margin-top:18px;">Akun baru dibuat oleh Admin melalui menu Manajemen Pengguna.</p>
    </div>
  </div>
</div>
</body>
</html>

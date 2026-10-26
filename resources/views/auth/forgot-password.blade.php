<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lupa Kata Sandi — SIM Sekolah SMKN 4</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth-shell">
  <div class="auth-side">
    <div class="brand" style="border:none;padding:0;"><img class="brand-logo" src="{{ asset('images/logo-smkn4-tanjungpinang.png') }}" alt="Logo SMKN 4 Tanjungpinang"><div class="brand-text"><div class="t1">SIM Sekolah</div><div class="t2" style="color:#8FA6BC;">SMKN 4 Tanjungpinang</div></div></div>
    <div><div class="quote">Atur ulang kata sandi akun Anda melalui email sekolah yang terdaftar.</div></div>
    <div style="font-size:12px;color:#6C87A0;">&copy; {{ date('Y') }} SMKN 4 Tanjungpinang</div>
  </div>
  <div class="auth-form-wrap"><div class="auth-card">
    <h1>Lupa kata sandi?</h1>
    <p class="sub">Masukkan alamat email akun Anda. Jika terdaftar, kami akan mengirim tautan untuk membuat kata sandi baru.</p>
    @if (session('status'))<div class="hint" style="margin-bottom:18px;">{{ session('status') }}</div>@endif
    @if ($errors->any())<div style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:18px;">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}">
      @csrf
      <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></div>
      <button type="submit" class="btn btn-primary btn-block">Kirim tautan reset</button>
    </form>
    <p style="font-size:13px;text-align:center;margin-top:18px;"><a href="{{ route('login') }}" style="color:var(--primary);font-weight:600;">Kembali ke halaman masuk</a></p>
  </div></div>
</div>
</body>
</html>

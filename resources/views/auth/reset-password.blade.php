<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Atur Kata Sandi — SIM Sekolah SMKN 4</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth-shell"><div class="auth-side"><div class="brand" style="border:none;padding:0;"><img class="brand-logo" src="{{ asset('images/logo-smkn4-tanjungpinang.png') }}" alt="Logo SMKN 4 Tanjungpinang"><div class="brand-text"><div class="t1">SIM Sekolah</div><div class="t2" style="color:#8FA6BC;">SMKN 4 Tanjungpinang</div></div></div><div><div class="quote">Pilih kata sandi baru untuk akun Anda.</div></div><div style="font-size:12px;color:#6C87A0;">&copy; {{ date('Y') }} SMKN 4 Tanjungpinang</div></div>
<div class="auth-form-wrap"><div class="auth-card">
  <h1>Atur kata sandi baru</h1>
  @if ($errors->any())<div style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:18px;">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"></div>
    <div class="field"><label>Kata sandi baru</label><input type="password" name="password" required minlength="8" autocomplete="new-password"></div>
    <div class="field"><label>Ulangi kata sandi baru</label><input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
    <button type="submit" class="btn btn-primary btn-block">Simpan kata sandi</button>
  </form>
</div></div></div>
</body>
</html>

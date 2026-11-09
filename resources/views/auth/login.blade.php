<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — SIM Sekolah SMKN 4</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<div class="auth-shell">
  <header class="auth-brand">
      <img class="login-brand-logo" src="{{ asset('images/logo-smkn4-tanjungpinang.jpg') }}" alt="Logo SMK Negeri 4 Tanjungpinang">
      <div class="auth-brand__text">
        <div class="auth-brand__name">SIM Sekolah</div>
        <div class="auth-brand__school">SMKN 4 Tanjungpinang</div>
      </div>
  </header>

  <main class="auth-form-wrap">
    <section class="auth-card" aria-labelledby="loginTitle">
      <div class="auth-card__mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10 4H5v16h5" />
          <path d="m13 8 4 4-4 4" />
          <path d="M8 12h9" />
        </svg>
      </div>
      <h1 id="loginTitle">Masuk ke akun Anda</h1>
      <p class="sub">Gunakan akun yang terdaftar di sekolah untuk mengakses sistem.</p>

      @if (session('status'))
        <div class="auth-alert auth-alert--success" role="status">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="auth-alert auth-alert--danger" role="alert">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('login.submit') }}">
        @csrf

        <div class="field">
          <label for="loginEmail">Email</label>
          <input id="loginEmail" type="email" name="email" value="{{ old('email') }}" placeholder="nama@smkn4tanjungpinang.sch.id" required autofocus autocomplete="username">
        </div>
        <div class="field">
          <label for="loginPassword">Kata Sandi</label>
          <input id="loginPassword" type="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Masuk</button>
      </form>

      <p class="auth-card__note">Akun baru dibuat oleh Admin melalui menu Manajemen Pengguna.</p>
    </section>
  </main>

  <footer class="auth-footer">&copy; {{ date('Y') }} SMKN 4 Tanjungpinang</footer>
</div>
</body>
</html>

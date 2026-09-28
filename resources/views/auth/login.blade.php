<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — SIM SMKN 4</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

    <div class="login-screen">

        <aside class="login-aside">
            <div>
                <div class="mark">SISTEM INFORMASI MANAJEMEN</div>
            </div>

            <div>
                <h1>Satu pintu untuk surat, kerja sama industri, dan PKL siswa.</h1>
                <p>Semua disposisi, dokumen kerja sama, dan penempatan PKL tercatat di satu tempat — bisa ditelusuri kapan saja.</p>
            </div>

            <div class="foot">SMK Negeri 4</div>
        </aside>

        <main class="login-main">
            <div class="login-form-wrap">
                <h2>Masuk ke akun Anda</h2>
                <p class="subtitle">Gunakan email dan kata sandi yang terdaftar.</p>

                @if ($errors->any())
                    <div class="form-alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>

                    <div class="field">
                        <label for="password">Kata sandi</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn-primary">Masuk</button>
                </form>
            </div>
        </main>

    </div>

</body>
</html>

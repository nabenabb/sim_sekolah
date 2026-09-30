<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Dashboard') — SIM Sekolah SMKN 4</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
  $roleName = auth()->user()->role->name;
  $canSee = fn (array $roles) => in_array($roleName, $roles);

  $suratRoles = ['Koordinator TU', 'Admin', 'Kepala Sekolah'];
  $penerimaRoles = \App\Models\Disposition::ROLE_PENERIMA;
  $mitraRoles = ['Waka Humas', 'Kepala Program Keahlian', 'Kepala Sekolah', 'Admin'];
  $pklRoles = ['Waka Humas', 'Admin'];
@endphp
<div class="app">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark">S4</div>
      <div class="brand-text"><div class="t1">SIM Sekolah</div><div class="t2">SMKN 4 Tanjungpinang</div></div>
    </div>
    <nav class="nav-group">
      <div class="nav-label">UTAMA</div>
      <a class="nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="ic">&#9670;</span><span>Dashboard</span></a>

      @if ($canSee($suratRoles) || $canSee($penerimaRoles))
        <div class="nav-label">MODUL SURAT</div>
        @if ($canSee($suratRoles))
          <a class="nav-item {{ request()->routeIs('surat-masuk*') ? 'active' : '' }}" href="{{ route('surat-masuk.index') }}"><span class="ic">&#9636;</span><span>Surat Masuk & Disposisi</span></a>
        @endif
        @if ($canSee($penerimaRoles))
          <a class="nav-item {{ request()->routeIs('disposisi-saya*') ? 'active' : '' }}" href="{{ route('disposisi-saya.index') }}"><span class="ic">&#9636;</span><span>Disposisi Saya</span></a>
        @endif
        @if ($canSee($suratRoles))
          <a class="nav-item {{ request()->routeIs('surat-keluar*') ? 'active' : '' }}" href="{{ route('surat-keluar.index') }}"><span class="ic">&#9637;</span><span>Surat Keluar</span></a>
        @endif
      @endif

      @if ($canSee($mitraRoles) || $canSee($pklRoles))
        <div class="nav-label">KERJA SAMA & PKL</div>
        @if ($canSee($mitraRoles))
          <a class="nav-item {{ request()->routeIs('mitra-industri*') ? 'active' : '' }}" href="{{ route('mitra-industri.index') }}"><span class="ic">&#9672;</span><span>Mitra & Kerja Sama Industri</span></a>
        @endif
        @if ($canSee($pklRoles))
          <a class="nav-item {{ request()->routeIs('pkl*') ? 'active' : '' }}" href="{{ route('pkl.index') }}"><span class="ic">&#9673;</span><span>Data Siswa PKL</span></a>
        @endif
      @endif

      @if ($canSee($pklRoles))
        <div class="nav-label">SERTIFIKAT</div>
        <a class="nav-item {{ request()->routeIs('sertifikat*') ? 'active' : '' }}" href="{{ route('sertifikat.index') }}"><span class="ic">&#9639;</span><span>Sertifikat PKL</span></a>
      @endif

      @if ($roleName === 'Admin')
        <div class="nav-label">ADMINISTRASI</div>
        <a class="nav-item {{ request()->routeIs('manajemen-pengguna*') ? 'active' : '' }}" href="{{ route('manajemen-pengguna.index') }}"><span class="ic">&#9673;</span><span>Manajemen Pengguna</span></a>
      @endif
    </nav>
    <div class="sidebar-foot">v1.0 &middot; SIM Sekolah SMKN 4</div>
  </aside>

  <div class="main">
    <header class="topbar">
      <div class="topbar-title">
        <h1>@yield('page-title', 'Dashboard')</h1>
        <div class="crumb">@yield('breadcrumb', '')</div>
      </div>
      <div class="topbar-right">
        <form method="GET" action="{{ route('cari.index') }}" class="search-mini" style="cursor:text;">
          <span class="ic">&#8981;</span>
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari..." autocomplete="off"
                 style="border:none;background:none;font:inherit;font-size:13px;color:inherit;outline:none;width:120px;">
        </form>

        <div style="position:relative;">
          <button type="button" class="bell" onclick="toggleNotif(event)" style="cursor:pointer;border:none;">
            <span class="ic">&#128276;</span>
            @if ($notifCount > 0)<span class="dot"></span>@endif
          </button>
          <div id="notifPanel" style="display:none;position:absolute;right:0;top:calc(100% + 10px);width:320px;background:#fff;border:1px solid var(--border);border-radius:var(--radius-card);box-shadow:0 12px 28px rgba(16,37,61,0.18);z-index:50;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid var(--border);font-size:13.5px;font-weight:700;">Notifikasi ({{ $notifCount }})</div>
            <div style="max-height:320px;overflow-y:auto;">
              @forelse ($notifItems as $n)
                <a href="{{ $n['url'] }}" style="display:block;padding:11px 16px;font-size:12.5px;color:var(--ink);text-decoration:none;border-bottom:1px solid var(--border-soft, var(--border));">
                  {{ $n['label'] }}
                </a>
              @empty
                <div style="padding:16px;font-size:12.5px;color:var(--ink-faint);text-align:center;">Tidak ada notifikasi baru.</div>
              @endforelse
            </div>
          </div>
        </div>

        <div class="account">
          <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
          <div class="account-text">
            <div class="name">{{ auth()->user()->name }}</div>
            <div class="role">{{ $roleName }}</div>
          </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
          @csrf
          <button type="submit" class="btn btn-ghost btn-sm">Keluar</button>
        </form>
      </div>
    </header>

    <div class="content">
      @if (session('success'))
        <div style="background:var(--success-tint);color:var(--success);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:18px;">
          {{ session('success') }}
        </div>
      @endif

      {{-- Banner error global. Halaman yang menampilkan error sendiri cukup menambah @section('no-error-banner', '1') --}}
      @if ($errors->any() && ! $__env->hasSection('no-error-banner'))
        <div style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:18px;">
          {{ $errors->first() }}
        </div>
      @endif

      @yield('content')
    </div>
  </div>
</div>

<script>
  function toggleNotif(e) {
    e.stopPropagation();
    const panel = document.getElementById('notifPanel');
    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
  }
  document.addEventListener('click', () => {
    const panel = document.getElementById('notifPanel');
    if (panel) panel.style.display = 'none';
  });
</script>
@yield('scripts')
</body>
</html>

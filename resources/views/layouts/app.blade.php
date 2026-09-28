<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Dashboard') — SIM Sekolah SMKN 4</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark">S4</div>
      <div class="brand-text"><div class="t1">SIM Sekolah</div><div class="t2">SMKN 4 Tanjungpinang</div></div>
    </div>
    <nav class="nav-group">
      <div class="nav-label">UTAMA</div>
      <a class="nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="ic">&#9670;</span><span>Dashboard</span></a>

      <div class="nav-label">MODUL SURAT</div>
      <a class="nav-item {{ request()->routeIs('surat-masuk*') ? 'active' : '' }}" href="{{ route('surat-masuk.index') }}"><span class="ic">&#9636;</span><span>Surat Masuk & Disposisi</span></a>
      <a class="nav-item {{ request()->routeIs('surat-keluar*') ? 'active' : '' }}" href="{{ route('surat-keluar.index') }}"><span class="ic">&#9637;</span><span>Surat Keluar</span></a>

      <div class="nav-label">KERJA SAMA & PKL</div>
      <a class="nav-item {{ request()->routeIs('mitra-industri*') ? 'active' : '' }}" href="{{ route('mitra-industri.index') }}"><span class="ic">&#9672;</span><span>Mitra & Kerja Sama Industri</span></a>
      <a class="nav-item {{ request()->routeIs('pkl*') ? 'active' : '' }}" href="{{ route('pkl.index') }}"><span class="ic">&#9673;</span><span>Data Siswa PKL</span></a>

      <div class="nav-label">SERTIFIKAT</div>
      <a class="nav-item {{ request()->routeIs('sertifikat*') ? 'active' : '' }}" href="{{ route('sertifikat.index') }}"><span class="ic">&#9639;</span><span>Sertifikat PKL</span></a>
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
        <div class="search-mini"><span class="ic">&#8981;</span><span>Cari&hellip;</span></div>
        <div class="bell"><span class="ic">&#128276;</span><span class="dot"></span></div>
        <div class="account">
          <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
          <div class="account-text">
            <div class="name">{{ auth()->user()->name }}</div>
            <div class="role">{{ auth()->user()->role->name }}</div>
          </div>
        </div>
      </div>
    </header>

    <div class="content">
      @if (session('success'))
        <div style="background:var(--success-tint);color:var(--success);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:18px;">
          {{ session('success') }}
        </div>
      @endif

      @yield('content')
    </div>
  </div>
</div>

@yield('scripts')
</body>
</html>

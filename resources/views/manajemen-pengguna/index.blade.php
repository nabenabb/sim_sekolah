@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')
@section('breadcrumb', 'Admin / Pengguna')
@section('no-error-banner', '1')

@section('content')

<div class="card card-pad">
  <div class="card-head">
    <div><h2>Daftar Pengguna</h2><div class="sub">{{ $users->total() }} akun terdaftar</div></div>
    <button class="btn btn-primary btn-sm" onclick="openModal('modalTambah')">+ Tambah Pengguna</button>
  </div>

  @if ($errors->any())
    <div style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:14px;">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="GET" action="{{ route('manajemen-pengguna.index') }}" style="margin-bottom:14px;">
    <input type="hidden" name="status" value="{{ $statusFilter }}">
    <input type="hidden" name="role" value="{{ request('role') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..."
           style="width:100%;max-width:360px;border:1px solid var(--border-strong);border-radius:var(--radius-control);padding:8px 12px;font:inherit;font-size:13px;background:var(--surface-sunken);"
           onchange="this.form.submit()">
  </form>

  <div class="filter-bar">
    <a href="{{ route('manajemen-pengguna.index', array_filter(['status' => 'semua', 'role' => request('role'), 'q' => request('q')])) }}" class="chip {{ $statusFilter === 'semua' ? 'active' : '' }}">Semua</a>
    <a href="{{ route('manajemen-pengguna.index', array_filter(['status' => 'aktif', 'role' => request('role'), 'q' => request('q')])) }}" class="chip {{ $statusFilter === 'aktif' ? 'active' : '' }}">Aktif</a>
    <a href="{{ route('manajemen-pengguna.index', array_filter(['status' => 'nonaktif', 'role' => request('role'), 'q' => request('q')])) }}" class="chip {{ $statusFilter === 'nonaktif' ? 'active' : '' }}">Nonaktif</a>

    <form method="GET" action="{{ route('manajemen-pengguna.index') }}" style="display:inline;">
      <input type="hidden" name="status" value="{{ $statusFilter }}">
      <input type="hidden" name="q" value="{{ request('q') }}">
      <select name="role" class="select-mini" onchange="this.form.submit()">
        <option value="">Semua role</option>
        @foreach ($roles as $r)
          <option value="{{ $r->id }}" {{ request('role') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
        @endforeach
      </select>
    </form>
  </div>

  <table>
    <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Program Keahlian</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($users as $u)
        <tr>
          <td class="cell-strong">{{ $u->name }}</td>
          <td>{{ $u->email }}</td>
          <td>{{ $u->role->name }}</td>
          <td>{{ $u->programKeahlian->nama_jurusan ?? '—' }}</td>
          <td>
            @if ($u->is_active)
              <span class="pill pill-success"><span class="dot"></span>Aktif</span>
            @else
              <span class="pill pill-danger"><span class="dot"></span>Nonaktif</span>
            @endif
          </td>
          <td style="white-space:nowrap;">
            <a class="link-more" href="#" onclick="openModal('modalEdit{{ $u->id }}');return false;">Edit</a>
            &nbsp;·&nbsp;
            <a class="link-more" href="#" onclick="openModal('modalReset{{ $u->id }}');return false;">Reset Sandi</a>
            @if ($u->id !== auth()->id())
              &nbsp;·&nbsp;
              <form method="POST" action="{{ route('manajemen-pengguna.toggle', $u->id) }}" style="display:inline;" onsubmit="return confirm('{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }} user {{ $u->name }}?')">
                @csrf
                @method('PATCH')
                <button type="submit" class="link-more" style="background:none;border:none;padding:0;cursor:pointer;color:{{ $u->is_active ? 'var(--danger)' : 'var(--success)' }};">
                  {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;color:var(--ink-faint);padding:24px;">Belum ada pengguna.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($users->hasPages())
    <div style="display:flex;justify-content:center;gap:8px;padding:16px;">
      @if ($users->onFirstPage())
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">&larr; Sebelumnya</span>
      @else
        <a href="{{ $users->previousPageUrl() }}" class="btn btn-ghost btn-sm">&larr; Sebelumnya</a>
      @endif
      <span style="font-size:12.5px;color:var(--ink-faint);align-self:center;">Hal. {{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
      @if ($users->hasMorePages())
        <a href="{{ $users->nextPageUrl() }}" class="btn btn-ghost btn-sm">Berikutnya &rarr;</a>
      @else
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">Berikutnya &rarr;</span>
      @endif
    </div>
  @endif
</div>

{{-- ================= MODAL TAMBAH PENGGUNA ================= --}}
<div class="modal-overlay" id="modalTambah">
  <div class="modal-card">
    <form method="POST" action="{{ route('manajemen-pengguna.store') }}">
      @csrf
      <div class="modal-head">
        <h2>Tambah Pengguna</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalTambah')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="field"><label>Nama Lengkap</label><input type="text" name="name" value="{{ old('name') }}" required></div>
        <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
        <div class="field"><label>Password Awal</label><input type="text" name="password" placeholder="minimal 5 karakter" minlength="5" required></div>
        <div class="field-row">
          <div class="field">
            <label>Role</label>
            <select name="role_id" required>
              <option value="">— Pilih role —</option>
              @foreach ($roles as $r)
                <option value="{{ $r->id }}" {{ old('role_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label>Program Keahlian <span class="hint">(opsional)</span></label>
            <select name="program_keahlian_id">
              <option value="">—</option>
              @foreach ($programKeahlian as $pk)
                <option value="{{ $pk->id }}" {{ old('program_keahlian_id') == $pk->id ? 'selected' : '' }}>{{ $pk->nama_jurusan }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="hint">Isi Program Keahlian kalau role-nya Guru atau Kepala Program Keahlian.</div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalTambah')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
      </div>
    </form>
  </div>
</div>

{{-- ================= MODAL EDIT & RESET PER USER ================= --}}
@foreach ($users as $u)
  <div class="modal-overlay" id="modalEdit{{ $u->id }}">
    <div class="modal-card">
      <form method="POST" action="{{ route('manajemen-pengguna.update', $u->id) }}">
        @csrf
        @method('PUT')
        <div class="modal-head">
          <h2>Edit Pengguna</h2>
          <button type="button" class="modal-close" onclick="closeModal('modalEdit{{ $u->id }}')">&times;</button>
        </div>
        <div class="modal-body">
          <div class="field"><label>Nama Lengkap</label><input type="text" name="name" value="{{ $u->name }}" required></div>
          <div class="field"><label>Email</label><input type="email" name="email" value="{{ $u->email }}" required></div>
          <div class="field-row">
            <div class="field">
              <label>Role</label>
              <select name="role_id" required>
                @foreach ($roles as $r)
                  <option value="{{ $r->id }}" {{ $u->role->name === $r->name ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="field">
              <label>Program Keahlian</label>
              <select name="program_keahlian_id">
                <option value="">—</option>
                @foreach ($programKeahlian as $pk)
                  <option value="{{ $pk->id }}" {{ $u->program_keahlian_id == $pk->id ? 'selected' : '' }}>{{ $pk->nama_jurusan }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modalEdit{{ $u->id }}')">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="modalReset{{ $u->id }}">
    <div class="modal-card">
      <form method="POST" action="{{ route('manajemen-pengguna.resetPassword', $u->id) }}">
        @csrf
        <div class="modal-head">
          <h2>Reset Sandi &mdash; {{ $u->name }}</h2>
          <button type="button" class="modal-close" onclick="closeModal('modalReset{{ $u->id }}')">&times;</button>
        </div>
        <div class="modal-body">
          <div class="hint" style="margin-bottom:14px;">Sandi lama langsung tidak berlaku begitu diganti. Beri tahu sandi baru ini ke yang bersangkutan secara langsung.</div>
          <div class="field"><label>Sandi Baru</label><input type="text" name="password" placeholder="minimal 5 karakter" minlength="5" required></div>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-ghost" onclick="closeModal('modalReset{{ $u->id }}')">Batal</button>
          <button type="submit" class="btn btn-primary">Reset Sandi</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

@endsection

@section('scripts')
<script>
  function openModal(id){ document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
  function closeModal(id){ document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
  document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ document.querySelectorAll('.modal-overlay.open').forEach(m=>closeModal(m.id)); } });
</script>
@endsection

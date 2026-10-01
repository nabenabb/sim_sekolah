@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page-title', 'Data Siswa')
@section('breadcrumb', 'PKL / Data Siswa')
@section('no-error-banner', '1')

@section('content')

<div class="card card-pad">
  <div class="card-head">
    <div>
      <h2>Data Siswa</h2>
      <div class="sub">{{ $students->total() }} siswa terdaftar — dipakai untuk penempatan PKL</div>
    </div>
    <button class="btn btn-primary btn-sm" onclick="openModal('modalSiswa')">+ Tambah Siswa</button>
  </div>

  @if ($errors->any())
    <div style="background:var(--danger-tint);color:var(--danger);border-radius:var(--radius-control);padding:10px 14px;font-size:13px;margin-bottom:14px;">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="GET" action="{{ route('siswa.index') }}" style="margin-bottom:14px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau NIS…"
           style="flex:1;min-width:200px;border:1px solid var(--border-strong);border-radius:var(--radius-control);padding:8px 12px;font:inherit;font-size:13px;background:var(--surface-sunken);">
    <select name="jurusan" class="select-mini" onchange="this.form.submit()">
      <option value="">Semua jurusan</option>
      @foreach ($programKeahlian as $pk)
        <option value="{{ $pk->id }}" {{ request('jurusan') == $pk->id ? 'selected' : '' }}>{{ $pk->nama_jurusan }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-ghost btn-sm">Cari</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>NIS</th>
        <th>Nama</th>
        <th>Program Keahlian</th>
        <th>Tgl Lahir</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($students as $s)
        <tr>
          <td class="mono">{{ $s->nis }}</td>
          <td class="cell-strong">{{ $s->nama }}</td>
          <td>{{ $s->programKeahlian->nama_jurusan ?? '—' }}</td>
          <td>{{ $s->tanggal_lahir?->translatedFormat('d M Y') ?? '—' }}</td>
          <td style="white-space:nowrap;">
            <a class="link-more" href="{{ route('siswa.index', array_filter(['edit' => $s->id, 'q' => request('q'), 'jurusan' => request('jurusan')])) }}">Edit</a>
            &nbsp;·&nbsp;
            <form method="POST" action="{{ route('siswa.destroy', $s) }}" style="display:inline;"
                  onsubmit="return confirm('Yakin hapus siswa {{ $s->nama }} dari daftar?')">
              @csrf
              @method('DELETE')
              <button type="submit" class="link-more" style="background:none;border:none;padding:0;cursor:pointer;color:var(--danger);">Hapus</button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align:center;color:var(--ink-faint);padding:28px;">
            Belum ada data siswa. Klik “+ Tambah Siswa” untuk menambah.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @if ($students->hasPages())
    <div style="display:flex;justify-content:center;gap:8px;padding:16px;">
      @if ($students->onFirstPage())
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">&larr; Sebelumnya</span>
      @else
        <a href="{{ $students->previousPageUrl() }}" class="btn btn-ghost btn-sm">&larr; Sebelumnya</a>
      @endif
      <span style="font-size:12.5px;color:var(--ink-faint);align-self:center;">Hal. {{ $students->currentPage() }} / {{ $students->lastPage() }}</span>
      @if ($students->hasMorePages())
        <a href="{{ $students->nextPageUrl() }}" class="btn btn-ghost btn-sm">Berikutnya &rarr;</a>
      @else
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">Berikutnya &rarr;</span>
      @endif
    </div>
  @endif
</div>

{{-- Modal Tambah / Edit --}}
<div class="modal-overlay {{ $editing || ($errors->any() && ! request('edit') && old('nama')) ? 'open' : '' }}" id="modalSiswa">
  <div class="modal-card" style="max-width:520px;">
    <form method="POST" action="{{ $editing ? route('siswa.update', $editing) : route('siswa.store') }}">
      @csrf
      @if ($editing) @method('PUT') @endif
      <div class="modal-head">
        <h2>{{ $editing ? 'Edit Siswa' : 'Tambah Siswa' }}</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalSiswa'); window.location='{{ route('siswa.index', array_filter(['q' => request('q'), 'jurusan' => request('jurusan')])) }}';">&times;</button>
      </div>
      <div class="modal-body">
        <div class="field">
          <label>Nama Lengkap</label>
          <input type="text" name="nama" value="{{ old('nama', $editing->nama ?? '') }}" placeholder="Nama siswa" required>
        </div>
        <div class="field-row">
          <div class="field">
            <label>NIS</label>
            <input type="text" name="nis" value="{{ old('nis', $editing->nis ?? '') }}" placeholder="Nomor Induk Siswa" required>
          </div>
          <div class="field">
            <label>Tanggal Lahir <span class="hint">(opsional)</span></label>
            <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', optional($editing->tanggal_lahir ?? null)->format('Y-m-d')) }}">
          </div>
        </div>
        <div class="field">
          <label>Program Keahlian</label>
          <select name="program_keahlian_id" required>
            @foreach ($programKeahlian as $pk)
              <option value="{{ $pk->id }}" {{ old('program_keahlian_id', $editing->program_keahlian_id ?? '') == $pk->id ? 'selected' : '' }}>
                {{ $pk->nama_jurusan }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-foot">
        <a href="{{ route('siswa.index', array_filter(['q' => request('q'), 'jurusan' => request('jurusan')])) }}" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">{{ $editing ? 'Simpan Perubahan' : 'Simpan Siswa' }}</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(id){ const el=document.getElementById(id); if(el) el.classList.add('open'); }
  function closeModal(id){ const el=document.getElementById(id); if(el) el.classList.remove('open'); }
  @if ($editing)
    document.addEventListener('DOMContentLoaded', () => openModal('modalSiswa'));
  @endif
</script>
@endsection

@extends('layouts.app')

@section('title', 'Surat Keluar')
@section('page-title', 'Surat Keluar')
@section('breadcrumb', 'Tata Usaha / Surat Keluar')

@section('content')

<div class="card card-pad">
  <div class="card-head">
    <div><h2>Daftar Surat Keluar</h2><div class="sub">Draf, menunggu tanda tangan, hingga terkirim</div></div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('form-surat-keluar').scrollIntoView({behavior:'smooth'})">+ Buat Surat Keluar</button>
  </div>

  <form method="GET" action="{{ route('surat-keluar.index') }}" style="margin-bottom:14px;">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari no. surat, tujuan, atau perihal…"
           style="width:100%;max-width:420px;border:1px solid var(--border-strong);border-radius:var(--radius-control);padding:8px 12px;font:inherit;font-size:13px;background:var(--surface-sunken);"
           onchange="this.form.submit()">
  </form>

  <div class="filter-bar">
    <a href="{{ route('surat-keluar.index', array_filter(['status' => 'semua', 'q' => request('q')])) }}" class="chip {{ $status === 'semua' ? 'active' : '' }}">Semua</a>
    <a href="{{ route('surat-keluar.index', array_filter(['status' => 'draf', 'q' => request('q')])) }}" class="chip {{ $status === 'draf' ? 'active' : '' }}">Draf</a>
    <a href="{{ route('surat-keluar.index', array_filter(['status' => 'menunggu_ttd', 'q' => request('q')])) }}" class="chip {{ $status === 'menunggu_ttd' ? 'active' : '' }}">Menunggu TTD</a>
    <a href="{{ route('surat-keluar.index', array_filter(['status' => 'terkirim', 'q' => request('q')])) }}" class="chip {{ $status === 'terkirim' ? 'active' : '' }}">Terkirim</a>
  </div>

  <table>
    <thead><tr><th>No. Surat</th><th>Tanggal</th><th>Tujuan</th><th>Perihal</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($letters as $letter)
        @php
          $pillClass = match($letter->status) {
            'terkirim' => 'pill-success',
            default => 'pill-warning',
          };
          $statusText = match($letter->status) {
            'draf' => 'Draf',
            'menunggu_ttd' => 'Menunggu TTD',
            'terkirim' => 'Terkirim',
          };
        @endphp
        <tr>
          <td class="mono">{{ $letter->no_surat }}</td>
          <td>{{ $letter->tanggal->translatedFormat('d M Y') }}</td>
          <td>{{ $letter->tujuan }}</td>
          <td class="cell-strong">{{ $letter->perihal }}</td>
          <td><span class="pill {{ $pillClass }}"><span class="dot"></span>{{ $statusText }}</span></td>
          <td style="white-space:nowrap;">
            <a class="link-more" href="#" onclick="openModal('modalLihat{{ $letter->id }}');return false;">Lihat</a>
            @if ($letter->status === 'draf')
              &nbsp;·&nbsp;<a class="link-more" href="{{ route('surat-keluar.index', ['edit' => $letter->id]) }}">Edit</a>
            @endif
            @if ($letter->status === 'menunggu_ttd' && auth()->user()->role->name === 'Kepala Sekolah')
              &nbsp;·&nbsp;
              <form method="POST" action="{{ route('surat-keluar.sign', $letter->id) }}" style="display:inline;">
                @csrf
                <button type="submit" class="link-more" style="background:none;border:none;padding:0;cursor:pointer;color:var(--success);">Tandatangani</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;color:var(--ink-faint);padding:24px;">Belum ada surat keluar.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($letters->hasPages())
    <div style="display:flex;justify-content:center;gap:8px;padding:16px;">
      @if ($letters->onFirstPage())
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">&larr; Sebelumnya</span>
      @else
        <a href="{{ $letters->previousPageUrl() }}" class="btn btn-ghost btn-sm">&larr; Sebelumnya</a>
      @endif
      <span style="font-size:12.5px;color:var(--ink-faint);align-self:center;">Hal. {{ $letters->currentPage() }} / {{ $letters->lastPage() }}</span>
      @if ($letters->hasMorePages())
        <a href="{{ $letters->nextPageUrl() }}" class="btn btn-ghost btn-sm">Berikutnya &rarr;</a>
      @else
        <span class="btn btn-ghost btn-sm" style="opacity:.4;">Berikutnya &rarr;</span>
      @endif
    </div>
  @endif
</div>

{{-- ================= FORM BUAT / EDIT SURAT KELUAR ================= --}}
<div class="card card-pad" id="form-surat-keluar" style="margin-top:20px;max-width:760px;">
  <div class="card-head"><h2>{{ $editing ? 'Edit Surat Keluar' : 'Buat Surat Keluar' }}</h2></div>

  <form method="POST" action="{{ $editing ? route('surat-keluar.update', $editing->id) : route('surat-keluar.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="field">
      <label>Berdasarkan Surat Masuk <span class="hint">(opsional)</span></label>
      <select name="ref_incoming_letter_id">
        <option value="">&mdash; Tidak berdasarkan surat masuk &mdash;</option>
        @foreach ($incomingLetters as $inc)
          <option value="{{ $inc->id }}" {{ optional($editing)->ref_incoming_letter_id == $inc->id ? 'selected' : '' }}>
            {{ $inc->no_agenda }} &mdash; {{ $inc->perihal }}
          </option>
        @endforeach
      </select>
    </div>
    <div class="field-row">
      <div class="field"><label>Tanggal Surat</label><input type="date" name="tanggal" value="{{ optional($editing)->tanggal?->format('Y-m-d') }}" required></div>
      <div class="field"><label>Alamat Tujuan</label><input type="text" name="tujuan" value="{{ optional($editing)->tujuan }}" placeholder="mis. PT Nusantara Digital Karya" required></div>
    </div>
    <div class="field"><label>Perihal</label><input type="text" name="perihal" value="{{ optional($editing)->perihal }}" placeholder="mis. Balasan Permohonan Kerja Sama" required></div>
    <div class="field"><label>Isi Ringkas</label><textarea name="isi_ringkas" placeholder="Ringkasan isi surat...">{{ optional($editing)->isi_ringkas }}</textarea></div>
    <div class="field">
      <label>Lampiran <span class="hint">(opsional)</span></label>
      <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.pdf">
      @if (optional($editing)->file_path)
        <div class="hint" style="margin-top:6px;">Sudah ada lampiran — upload file baru kalau mau ganti.</div>
      @endif
    </div>

    <div class="row" style="justify-content:flex-end;">
      @if ($editing)
        <a href="{{ route('surat-keluar.index') }}" class="btn btn-ghost">Batal</a>
      @endif
      <button type="submit" name="simpan_draf" value="1" class="btn btn-ghost">Simpan Draf</button>
      <button type="submit" name="ajukan" value="1" class="btn btn-primary">Ajukan ke Kepala Sekolah</button>
    </div>
  </form>
</div>

{{-- ================= MODAL DETAIL PER SURAT ================= --}}
@foreach ($letters as $letter)
  <div class="modal-overlay" id="modalLihat{{ $letter->id }}">
    <div class="modal-card">
      <div class="modal-head">
        <h2>Detail Surat Keluar</h2>
        <button type="button" class="modal-close" onclick="closeModal('modalLihat{{ $letter->id }}')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="info-list">
          <div class="info-row"><span class="k">No. Surat</span><span class="v mono">{{ $letter->no_surat }}</span></div>
          <div class="info-row"><span class="k">Tanggal</span><span class="v">{{ $letter->tanggal->translatedFormat('d M Y') }}</span></div>
          <div class="info-row"><span class="k">Tujuan</span><span class="v">{{ $letter->tujuan }}</span></div>
          <div class="info-row"><span class="k">Perihal</span><span class="v">{{ $letter->perihal }}</span></div>
          <div class="info-row"><span class="k">Isi Ringkas</span><span class="v">{{ $letter->isi_ringkas ?: '—' }}</span></div>
          <div class="info-row"><span class="k">Rujukan Surat Masuk</span><span class="v">{{ $letter->refLetter->no_agenda ?? '—' }}</span></div>
          <div class="info-row"><span class="k">Lampiran</span><span class="v">@if($letter->file_path)<a class="link-more" href="{{ asset('storage/'.$letter->file_path) }}" target="_blank">Lihat file</a>@else —@endif</span></div>
          <div class="info-row"><span class="k">Ditandatangani oleh</span><span class="v">{{ $letter->signer->name ?? '—' }}</span></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalLihat{{ $letter->id }}')">Tutup</button>
      </div>
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
